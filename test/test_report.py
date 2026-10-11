# Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
"""Execute generated production SQL against an isolated relational fixture.

SQLite exercises query/migration semantics; this is not a MySQL/Dolibarr integration test.
Run: python -m unittest discover -s test -v (PHP_BINARY can override php).
"""
import json
import os
from pathlib import Path
import shutil
import sqlite3
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parent.parent


class ReportTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        php = os.environ.get('PHP_BINARY') or shutil.which('php')
        if not php:
            raise unittest.SkipTest('PHP runtime required')
        with tempfile.TemporaryDirectory() as directory:
            for relative in ('compta/facture/class/facture.class.php', 'core/class/commonobjectline.class.php',
                             'core/class/commonobject.class.php', 'projet/class/project.class.php',
                             'core/modules/DolibarrModules.class.php', 'core/lib/files.lib.php',
                             'core/class/html.form.class.php', 'core/class/html.formfile.class.php'):
                path = Path(directory) / relative
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text('<?php\n', encoding='utf-8', newline='\n')
            result = subprocess.run([php, str(ROOT / 'test/report_contract.php')],
                                    env=dict(os.environ, CORE_FIXTURE_ROOT=directory), capture_output=True, text=True)
            if result.returncode:
                raise AssertionError(result.stdout + result.stderr)
            cls.contract = json.loads(result.stdout)

    def setUp(self):
        self.db = sqlite3.connect(':memory:')
        self.addCleanup(self.db.close)
        self.db.executescript('''
            CREATE TABLE erp_facture(rowid,entity,ref,fk_statut,type,fk_soc,ref_client,datef,
              date_lim_reglement,datec,tms,fk_mode_reglement,total_ht,total_tva,total_ttc,
              total_localtax1,total_localtax2,fk_projet,fk_user_author);
            CREATE TABLE erp_societe(rowid,entity,nom,email,code_client,code_compta,phone,address,zip,town,fk_pays);
            CREATE TABLE erp_c_country(rowid,code);
            CREATE TABLE erp_c_paiement(id,code);
            CREATE TABLE erp_projet(rowid,ref,title);
            CREATE TABLE erp_paiement_facture(fk_facture,fk_paiement,amount);
            CREATE TABLE erp_paiement(rowid,datep);
            CREATE TABLE erp_societe_remise_except(fk_facture,amount_ttc,datec,fk_facture_source,fk_facture_line);
            CREATE TABLE erp_societe_commerciaux(fk_soc,fk_user);
            INSERT INTO erp_societe(rowid,entity,nom) VALUES(1,1,'Assigned'),(2,2,'Other');
            INSERT INTO erp_facture(rowid,entity,ref,fk_statut,type,fk_soc,datef,total_ttc)
              VALUES(1,1,'F1',1,0,1,'2026-10-01',100),(2,2,'F2',1,0,2,'2026-10-01',200),
                    (3,1,'F3',2,0,1,'2026-10-01',50),(4,1,'F4',1,0,1,'2026-10-11',90);
            INSERT INTO erp_societe_commerciaux VALUES(1,7),(1,8),(2,8);
            INSERT INTO erp_paiement VALUES(1,'2026-10-09'),(2,'2026-10-10 18:00:00'),(3,'2026-10-11');
            INSERT INTO erp_paiement_facture VALUES(1,1,10),(1,2,20),(1,3,40),(3,3,50);
            INSERT INTO erp_societe_remise_except(fk_facture,amount_ttc,datec) VALUES(1,5,'2026-10-08'),(1,7,'2026-10-09');
        ''')

    def rows(self, name):
        self.db.row_factory = sqlite3.Row
        return self.db.execute(self.contract['queries'][name]).fetchall()

    def test_independent_aggregates_and_cutoff(self):
        row = self.rows('restricted')[0]
        self.assertEqual((row['am'], row['ream']), (30, 12))
        self.assertEqual(row['total_ttc'] - row['am'] - row['ream'], 58)

    def test_explicit_third_party_does_not_bypass_sales_scope(self):
        self.db.execute('DELETE FROM erp_societe_commerciaux WHERE fk_user=7')
        self.assertEqual(self.rows('restricted'), [])

    def test_shared_entity_filter_and_optional_project(self):
        self.assertEqual([row['rowid'] for row in self.rows('entity')], [2])
        self.assertEqual({row['rowid'] for row in self.rows('shared')}, {1, 2, 3})
        self.assertTrue(all(row['project_ref'] is None for row in self.rows('shared')))

    def test_external_user_scoped_to_own_third_party(self):
        self.assertEqual({row['fk_soc'] for row in self.rows('external')}, {1})

    def test_sql_pagination_keeps_total_count_and_balance(self):
        self.assertEqual([row['rowid'] for row in self.rows('page')], [2])
        row = self.rows('count')[0]
        self.assertEqual((row['total_rows'], row['total_owed']), (3, 308))

    def test_consumed_credit_reduces_source_and_target_once(self):
        self.db.execute("INSERT INTO erp_facture(rowid,entity,ref,fk_statut,type,fk_soc,datef,total_ttc) VALUES(5,2,'C5',1,2,2,'2026-10-01',-50)")
        self.db.execute("INSERT INTO erp_societe_remise_except(fk_facture,amount_ttc,datec,fk_facture_source) VALUES(2,25,'2026-10-09',5)")
        rows = {row['rowid']: row for row in self.rows('shared')}
        self.assertEqual(rows[2]['total_ttc'] - rows[2]['ream'], 175)
        self.assertEqual(rows[5]['total_ttc'] - rows[5]['ream'], -25)

    def test_rights_migration_preserves_entities_and_is_replayable(self):
        self.db.executescript('''
            CREATE TABLE erp_user_rights(entity,fk_user,fk_id,UNIQUE(entity,fk_user,fk_id));
            CREATE TABLE erp_usergroup_rights(entity,fk_usergroup,fk_id,UNIQUE(entity,fk_usergroup,fk_id));
            CREATE TABLE erp_document_model(nom,type,libelle,entity);
            CREATE TABLE erp_cronjob(module_name,entity,classesname,methodename,label,test,status,frequency);
            INSERT INTO erp_cronjob VALUES('customersowed',1,'/customersowed/class/actions_customersowed.class.php','sendMails','Custom label','1',1,7);
            INSERT INTO erp_user_rights VALUES(1,7,423100),(2,8,423101),(2,8,42310002);
            INSERT INTO erp_usergroup_rights VALUES(2,3,423102);
            INSERT INTO erp_document_model VALUES('custom','customersowed','Custom',1);
        ''')
        for _ in range(2):
            for sql in self.contract['migration']:
                self.db.execute(sql)
        self.assertEqual(self.db.execute('SELECT * FROM erp_user_rights ORDER BY entity,fk_id').fetchall(),
                         [(1, 7, 42310001), (2, 8, 42310002)])
        self.assertEqual(self.db.execute('SELECT * FROM erp_usergroup_rights').fetchall(), [(2, 3, 42310003)])
        self.assertEqual(self.db.execute('SELECT nom FROM erp_document_model').fetchall(), [('custom',)])
        self.assertEqual(self.db.execute('SELECT test,status,frequency FROM erp_cronjob').fetchall(), [('isModEnabled("customersowed")', 1, 7)])
        self.db.execute('DELETE FROM erp_user_rights WHERE fk_user=7')
        for sql in self.contract['migration']:
            self.db.execute(sql)
        self.assertEqual(self.db.execute('SELECT COUNT(*) FROM erp_user_rights WHERE fk_user=7').fetchone()[0], 0)
        self.assertEqual(self.contract['rights'], [42310001, 42310002, 42310003])


if __name__ == '__main__':
    unittest.main()
