import copy

from pytest_cases import CaseData, case_name



@case_name("Test dummy endpoint")
def case_send_empty_data() -> CaseData:
    expected_result = {"Response": "OK"}
    expected_status_code = 200

    return expected_result, expected_status_code
