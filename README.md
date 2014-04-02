X12
===

Generic ANSI X12 Transactions

Intended to be used as a template (<i>as each X12 implementation is dependent on specific business rules</i>)

<b>X12_811_Export.php</b><br />
ANSI ASC X12.811 (Financial Series [FIN]) Consolidated Service Invoice/Statement (version 004010)

<b>X12_835_Export.php</b><br />
ANSI ASC X12.835 Health Care Claim Payment/Advice transaction set ( version: 005010X221 )

<b>X12_999_Import.php</b><br />
ANSI ASC X12.999 Functional Acknowledgement ( version: 005010X231A1 )


<b>SQLCreate_X12_999_ErrorMessageTable:</b><br />
Creates a SQL table containing X12 999 response messages

<b>Import_X12_999:</b><br />
Creates a stored procedure that imports, parses and outputs an email containing the X12 999 response file with response messages.<br />
<i>Note: Requires SQLCreate_X12_999_ErrorMessageTable, created by the above</i>

<b>Create_TA1_InterChangeNoteCode:</b><br />
Creates and populates a table, tbl_TA1_InterChangeNoteCode that contains the X12 TA1 Interchange Note Codes and descriptions.</br />

