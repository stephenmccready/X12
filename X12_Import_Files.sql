Declare @path As VarChar(128), @InputFileName As VarChar(128)
Set @path='C:\' -- Replace this with your folder

-- Get list of files in the directory:
If OBJECT_ID('tempdb..#DirectoryListing') Is Not Null Drop Table #DirectoryListing;
Create Table #DirectoryListing (
       id int IDENTITY(1,1)
      ,InputFileName nvarchar(512)
      ,depth int
      ,isfile bit);

Insert #DirectoryListing (InputFileName,depth,isfile)
EXEC master.sys.xp_dirtree @path, 0, 1;

-- Target just X12 files
If OBJECT_ID('tempdb..#FilesForInput') Is Not Null Drop Table #FilesForInput;
Select	InputFileName
Into	#FilesForInput
From	#DirectoryListing As DL
Where	isfile = 1
And		DL.InputFileName Like '???%'	-- Put your filename criteria here

While Exists (Select InputFileName From #FilesForInput)
Begin
	Set @InputFileName = (Select Top 1 InputFileName From #FilesForInput)
	Print 'Exec dbo.usp_Import_X12 ' + @path + ',' + @InputFileName
	Exec Sandbox.dbo.usp_Import_X12 @path, @InputFileName
 
	Delete	
	From	#FilesForInput
	Where	InputFileName = @InputFileName
 
End
