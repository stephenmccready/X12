Declare @path As VarChar(128), @filename As VarChar(128)
Set @path='C:\'

-- Get list of files in the directory:
If OBJECT_ID('tempdb..#DirectoryListing') Is Not Null Drop Table #DirectoryListing;
Create Table #DirectoryListing (
       id int IDENTITY(1,1)
      ,InputFileName nvarchar(512)
      ,depth int
      ,isfile bit);

Insert #DirectoryListing (InputFileName,depth,isfile)
EXEC master.sys.xp_dirtree @path, 0, 1;

-- Target just the files you want to import
If OBJECT_ID('tempdb..#FilesForInput') Is Not Null Drop Table #FilesForInput;
Select	InputFileName
Into	#FilesForInput
From	#DirectoryListing As DL
Where	isfile = 1
And		DL.InputFileName Like '??%' -- Change this to your particular wildcard if needed

-- For each file, execute usp_Import_X12, which loads the file into tvl_X12_IN then formats and appends to table tbl_X12
Declare db_cursor Cursor For 
	Select InputFileName From #FilesForInput 
Open db_cursor
	Fetch Next From db_cursor Into @filename
	While @@FETCH_STATUS = 0
	Begin
		Print 'Exec Sandbox.dbo.usp_Import_X12 ' + @path + ',' + @filename
		Exec Sandbox.dbo.usp_Import_X12 @path, @filename
		Fetch Next From db_cursor Into @filename
	End
Close db_cursor
DeAllocate db_cursor
