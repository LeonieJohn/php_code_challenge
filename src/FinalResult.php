<?php

class FinalResult
{
    const EXPECTED_COLUMN_COUNT = 16;
    const CURRENCY_COLUMN = 0;
    const FAILURE_CODE_COLUMN = 1;
    const FAILURE_MESSAGE_COLUMN = 2;
    const BANK_BRANCH_CODE_COLUMN = 2;
    const BANK_ACCOUNT_NUMBER_COLUMN = 6;
    const BANK_ACCOUNT_NAME_COLUMN = 7;
    const AMOUNT_COLUMN = 8;
    const END_TO_END_ID_1_COLUMN = 10;
    const END_TO_END_ID_2_COLUMN = 11;
    const BANK_CODE_COLUMN = 3;
    const MIN_BANK_ACCOUNT_NUMBER_LENGTH = 6; // Example minimum length
    const MAX_BANK_ACCOUNT_NUMBER_LENGTH = 12; // Example maximum length

    public function results($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: " . $filePath);
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Unable to open file: " . $filePath);
        }

        $header = fgetcsv($handle);
        if (!$header || count($header) < 3) {
            fclose($handle);
            throw new Exception("Invalid CSV header");
        }

        $records = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) == self::EXPECTED_COLUMN_COUNT) {
                $amount = empty($row[self::AMOUNT_COLUMN]) || $row[self::AMOUNT_COLUMN] == "0" ? 0 : (float)$row[self::AMOUNT_COLUMN];
                $bankAccountNumber = $this->validateBankAccountNumber($row[self::BANK_ACCOUNT_NUMBER_COLUMN])
                    ? $row[self::BANK_ACCOUNT_NUMBER_COLUMN]
                    : "Invalid bank account number";
                $bankBranchCode = empty($row[self::BANK_BRANCH_CODE_COLUMN]) ? "Bank branch code missing" : $row[self::BANK_BRANCH_CODE_COLUMN];
                $endToEndId = empty($row[self::END_TO_END_ID_1_COLUMN]) && empty($row[self::END_TO_END_ID_2_COLUMN]) 
                    ? "End to end id missing" 
                    : $row[self::END_TO_END_ID_1_COLUMN] . $row[self::END_TO_END_ID_2_COLUMN];
                $bankCode = $this->validateBankCode($row[self::BANK_CODE_COLUMN])
                    ? $row[self::BANK_CODE_COLUMN]
                    : "Invalid bank code";

                $record = [
                    "amount" => [
                        "currency" => $header[self::CURRENCY_COLUMN],
                        "subunits" => (int)($amount * 100)
                    ],
                    "bank_account_name" => str_replace(" ", "_", strtolower($row[self::BANK_ACCOUNT_NAME_COLUMN])),
                    "bank_account_number" => $bankAccountNumber,
                    "bank_branch_code" => $bankBranchCode,
                    "bank_code" => $bankCode,
                    "end_to_end_id" => $endToEndId,
                ];

                $records[] = $record;
            }
        }

        fclose($handle);

        return [
            "filename" => basename($filePath),
            "document" => $filePath,
            "failure_code" => $header[self::FAILURE_CODE_COLUMN],
            "failure_message" => $header[self::FAILURE_MESSAGE_COLUMN],
            "records" => $records
        ];
    }

    /**
     * Validates the bank account number.
     *
     * @param string $bankAccountNumber The bank account number to validate.
     * @return bool True if the bank account number is valid, false otherwise.
     */
    private function validateBankAccountNumber($bankAccountNumber)
    {
        return ctype_digit($bankAccountNumber);
    }

    /**
     * Validates the bank code.
     *
     * @param string $bankCode The bank code to validate.
     * @return bool True if the bank code is valid, false otherwise.
     */
    private function validateBankCode($bankCode)
    {
        return ctype_digit($bankCode);
    }
}
