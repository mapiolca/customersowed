"""Isolated PHP regressions; core services are simulated, no database or email."""
import base64
import subprocess
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def run_php(body, sources):
    # Load only the module classes: dependencies are supplied by the fixture.
    code = "<?php\nerror_reporting(E_ALL);\n" + """set_error_handler(function ($severity, $message) {
        throw new ErrorException($message, 0, $severity);
    });
"""
    for source, class_name in sources:
        text = (ROOT / source).read_text(encoding="utf-8")
        text = text[text.index(("trait " if class_name == "CustomersOwedPdfLayout" else "class ") + class_name):]
        encoded = base64.b64encode(text.encode("utf-8")).decode("ascii")
        code += "eval(base64_decode('" + encoded + "'));\n"
    code += body
    with tempfile.TemporaryDirectory() as folder:
        script = Path(folder) / "regression.php"
        script.write_text(code, encoding="utf-8")
        result = subprocess.run(["php", str(script)], capture_output=True, text=True)
    if result.returncode:
        raise AssertionError(result.stdout + result.stderr)
    return result.stdout


class Regressions(unittest.TestCase):
    def test_hook_constructor_without_translation_or_company(self):
        output = run_php("""
        define('NOREQUIRETRAN', 1);
        define('NOREQUIRESOC', 1);
        ob_start();
        $hook = new ActionsCustomersOwed(new stdClass());
        if (ob_get_clean() !== '') { throw new Exception('Binary output polluted'); }
        if ($hook->outputlangs !== null) { throw new Exception('Eager translations'); }
        $hook->results = null;
        $hook->resprints = null;
        """, [("class/actions_customersowed.class.php", "ActionsCustomersOwed")])
        self.assertEqual(output, "")

    def test_report_line_constructor_without_dynamic_properties(self):
        run_php("""
        class CommonObject {}
        class CommonObjectLine {}
        class Facture { const STATUS_DRAFT = 0; }
        function getDolGlobalString($key, $default = '') { return $default; }
        function getDolGlobalInt($key, $default = 0) { return $default; }
        function dol_now() { return 1700000000; }
        $line = new CustomersOwedLine(new stdClass());
        if ($line->excludeDpInvoices !== false || $line->onlyLatePaymentInvoices !== false) {
            throw new Exception('Line defaults changed');
        }
        """, [("class/customersowed.class.php", "CustomersOwed")])

    def test_pdf_constructors_without_dynamic_properties(self):
        sources = [(str(p.relative_to(ROOT)), p.stem.split('.')[0])
                   for p in (ROOT / "core/modules/customersowed/doc").glob("pdf_*.php")]
        sources.insert(0, ("class/customersowedpdflayout.trait.php", "CustomersOwedPdfLayout"))
        run_php("""
        define('DOL_DOCUMENT_ROOT', '/simulated-core');
        class TestLangs {
            public function loadLangs($catalogues) {}
            public function trans($key) { return $key; }
        }
        function getDolGlobalString($key, $default = '') { return $default; }
        function getDolGlobalInt($key, $default = 0) { return $default; }
        function dol_now() { return 1700000000; }
        function pdf_getFormat() { return array('width' => 210, 'height' => 297); }
        $langs = new TestLangs();
        $conf = (object) array('theme' => 'eldy', 'global' => (object) array(
            'CUSTOMERSOWEDREPORT_WARNING_IMAGE_NAME' => 'none'
        ));
        foreach (array('pdf_standard', 'pdf_joliciel', 'pdf_nextep', 'pdf_malliaros') as $class) {
            $model = new $class(new stdClass());
            if (!in_array($model->page_largeur, array(210, 297))) { throw new Exception('Page format changed'); }
        }
        foreach (array('pdf_joliciel', 'pdf_malliaros') as $class) {
            $model = new $class(new stdClass(), 's.town$s.zip$state.nom$country.code_iso$typent.code');
            if ($model->numberColumnsToAdd !== 5 || count($model->columnPositions) < 5) {
                throw new Exception('Optional columns changed');
            }
        }
        """, sources)


if __name__ == "__main__":
    unittest.main()
