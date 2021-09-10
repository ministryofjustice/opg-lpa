import pytest

from pytest_cases import parametrize_with_cases

from perfplat.api.endpoints import handle_dummy

from .cases_handle_dummy import CasesHandleDummy


@parametrize_with_cases("expected_result,expected_status_code", cases=CasesHandleDummy)
def test_dummy(expected_result, expected_status_code):
    result, status_code = handle_dummy()
    assert result == expected_result
    assert status_code == expected_status_code
