import importlib.util
import json
from pathlib import Path
import subprocess
import sys
import unittest

SERVER = Path(__file__).resolve().parents[1] / 'tools' / 'admin-mcp.py'
spec = importlib.util.spec_from_file_location('admin_mcp', SERVER)
mcp = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mcp)


class FakeBackend:
    def __init__(self):
        self.calls = []

    def call(self, name, arguments):
        self.calls.append((name, arguments))
        return {'ok': True, 'result': {'name': 'Algodón'}}


class ProtocolTests(unittest.TestCase):
    def test_whitelist_and_unicode(self):
        backend = FakeBackend()
        result = mcp.dispatch({'jsonrpc': '2.0', 'id': 1, 'method': 'tools/call', 'params': {'name': 'ld_products_get', 'arguments': {'product_ids': [568]}}}, backend)
        self.assertFalse(result['result']['isError'])
        self.assertEqual(json.loads(result['result']['content'][0]['text']), result['result']['structuredContent'])
        forbidden = mcp.dispatch({'jsonrpc': '2.0', 'id': 2, 'method': 'tools/call', 'params': {'name': 'execute_sql'}}, backend)
        self.assertTrue(forbidden['result']['isError'])
        self.assertEqual(len(backend.calls), 1)

    def test_real_stdio_without_ssh(self):
        requests = [
            {'jsonrpc': '2.0', 'id': 1, 'method': 'initialize'},
            {'jsonrpc': '2.0', 'method': 'notifications/initialized'},
            {'jsonrpc': '2.0', 'id': 2, 'method': 'tools/list'},
            {'jsonrpc': '2.0', 'id': 3, 'method': 'ping'},
        ]
        process = subprocess.run([sys.executable, str(SERVER)], input=''.join(json.dumps(row) + '\n' for row in requests) + 'invalid-json\n', capture_output=True, text=True, encoding='utf-8', timeout=10)
        self.assertEqual(process.returncode, 0, process.stderr)
        responses = [json.loads(line) for line in process.stdout.splitlines()]
        self.assertEqual(len(responses), 4)
        self.assertEqual(responses[0]['result']['serverInfo']['name'], 'laboratorio-digital-admin')
        self.assertEqual(len(responses[1]['result']['tools']), 12)
        self.assertEqual(responses[2]['result'], {})
        self.assertEqual(responses[3]['error']['code'], -32700)


if __name__ == '__main__':
    unittest.main()
