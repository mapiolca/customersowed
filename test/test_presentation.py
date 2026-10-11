# Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
"""Presentation contracts with isolated PHP doubles; no live ERP or email delivery."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import unittest


class PresentationTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        php = os.environ.get('PHP_BINARY') or shutil.which('php')
        if not php:
            raise unittest.SkipTest('PHP runtime required')
        result = subprocess.run([php, str(Path(__file__).with_name('presentation_contract.php'))],
                                capture_output=True, text=True, encoding='utf-8')
        if result.returncode:
            raise RuntimeError(result.stdout + result.stderr)
        cls.result = json.loads(result.stdout)

    def test_email_escapes_business_values_and_limits_links(self):
        table = self.result['table']
        self.assertNotIn('<script>', table)
        self.assertIn('A &amp; B', table)
        self.assertNotIn('href=', table)
        self.assertIn('/compta/facture/card.php?id=1', self.result['linked'])
        self.assertIn('/societe/card.php?socid=7', self.result['linked'])

    def test_email_balances_include_partial_payment_and_open_credit(self):
        table = self.result['table']
        self.assertIn('>5</td>', table)
        self.assertIn('>-12</td>', table)
        footer = table.split('<tfoot>')[1]
        for amount in (88, 95, -7):
            self.assertIn(f'>{amount}</td>', footer)

    def test_empty_email_table_is_complete(self):
        table = self.result['empty']
        self.assertIn('colspan="6"', table)
        self.assertIn('NoRecordFound', table)
        self.assertTrue(table.endswith('</tr></tfoot></table>'))

    def test_unchanged_default_uses_newsletter_layout(self):
        template = self.result['template']
        self.assertIn('#f4f6f8', template)
        self.assertIn('__CUSTOMERSOWEDREPORT_INVOICES_LIST_TABLE__', template)
        self.assertNotIn('https://', template)

    def test_pdf_internal_header_has_only_issuer(self):
        texts = self.result['internal']
        self.assertIn('BillFrom', texts)
        self.assertNotIn('BillTo', texts)
        self.assertFalse(any('CustomerCode' in text for text in texts))

    def test_pdf_recipient_header_has_address_and_customer_reference(self):
        texts = self.result['recipient']
        self.assertIn('BillTo', texts)
        self.assertIn('CustomerCode : CU007', texts)
        self.assertTrue(any(text.startswith('Recipient\n') for text in texts))


if __name__ == '__main__':
    unittest.main()
