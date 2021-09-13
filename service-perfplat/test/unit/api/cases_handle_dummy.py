class CasesHandleDummy:

    def case_send_empty_data(self):
        expected_result = {"Response": "OK"}
        expected_status_code = 200
        return (expected_result, expected_status_code)
