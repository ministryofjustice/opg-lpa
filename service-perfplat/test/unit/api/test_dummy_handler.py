import pytest

from pytest_cases import CaseDataGetter, cases_data

from test.unit.api import cases_handle_dummy
from perfplat.api.endpoints import handle_dummy

@cases_data(module=cases_handle_dummy)
def test_dummy(case_data: CaseDataGetter):
    expected_result, expected_status_code = case_data.get()

    result, status_code = handle_dummy()

    assert result == expected_result
    assert status_code == expected_status_code
