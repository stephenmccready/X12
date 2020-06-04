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

<b>SQLCreate_TA1_InterChangeNoteCode:</b><br />
Creates and populates a table, tbl_TA1_InterChangeNoteCode that contains the X12 TA1 Interchange Note Codes and descriptions.<br />

<b>Import_X12_TA1:</b><br />
Creates a stored procedure that imports, parses and outputs an email containing the X12 TA1 acknowledgement file with response messages.<br />
<i>Note: Requires SQLCreate_X12_TA1_InterChangeNoteCode, created by the above</i>

<b>usp_Import_X12</b><br />
T-SQL only. Imports an X12 into a table and groups the segments by Hierarchical Level so that related data segments may be grouped together. <i>(this was a bit of a hack I used to do a quick and dirty reconciliation, but it's come in useful for ad hoc tasks involving X12 formatted files</i>)<br />
See also:<br />
X12_Import_CreateTables.sql<br />
X12_Import_Files.sql<br />

<b>X12_270_Export_Template.sql</b><br />
Template for outputting an X12 270 Eligibility Inquiry using MS SQL<br />
