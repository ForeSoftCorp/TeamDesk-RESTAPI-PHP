# Methods

* [Construction](#construct)
* [User](#user)
* [Describe](#describe)
* [Describe (Table)](#describetable)
* [Select](#select)
* [SelectTop](#selecttop)
* [Retrieve](#retrieve)
* [RetrieveByKey](#retrievebykey)
* [Create/Update/Upsert](#upsert)
* [Delete](#delete)
* [DeleteByKey](#deletebykey)
* [Document](#document)
* [Updated](#updated)
* [Deleted](#deleted)
* [Attachments](#attachments)
* [AttachmentsByKey](#attachmentsbykey)
* [Attachment](#attachment)
* [AttachmentByKey](#attachmentbykey)

Following methods are useful for debugging and return value only if `trace` option is `true`:

* [getLastRequest](#getLastRequest)
* [getLastResponse](#getLastResponse)
* [dump](#dump)

## Construction {#construct}

~~~ PHP
$restAPI = new TeamDesk\RestAPI(array $options);
~~~

**Parameters**

* `$options` is an associative array of name=>value properties. 

Following properties are supported:

* `"database"` - Database identifier. Integer or string, required. 
* `"host"` - Host name. String, optional. Defaults to "www.teamdesk.net".
* `"user"` - User's email. String.
* `"password"` - User's password. String.
* `"token"` - API authorization token. String.

   Either token or user/password pair is required.

* `"no-workflow"` - Disables execution of triggers. Boolean, optional, default to false. If true, requires the user to have Setup | Manage Data privilege.
* `"trace"` - Enables request-response tracing. Boolean, optional, defaults to false.
* `"variables"` - An associative array of name=>value pairs. Optional.
* `"cache"` - An instance of TeamDesk\IHttpCache derived class. Optional.
* `"ssl-verification"` - Controls SSL certificate verification. Defaults to true.

   Last option may be needed for old PHP builds that have built-in root certificates heavily outdated and fail certificate validation. In this case you can either disable SSL verification by setting option's value to `false` (not recommended), or provide the path to `.pem` file containing [up-to-date](https://curl.haxx.se/docs/caextract.html) root certificates.

---

## User {#user}

~~~ PHP
function User() : stdClass
~~~

Retrieves user's metadata.

**Returns**

User's descriptor as `stdClass`.

~~~ PHP
$result = $restAPI->User();
print "Hello " . $result->firstName;
~~~

---

## Describe {#describe}

~~~ PHP
function Describe() : stdClass
~~~

Retrieves database metadata.

**Returns**

Database descriptor as `stdClass`.

~~~ PHP
$result = $restAPI->Describe();
print $result->tables[0]->recordsName;
~~~

---

## Describe(table) {#describetable}

~~~ PHP
function Describe(string $table) : stdClass
~~~

Retrieves table metadata. 

**Parameters**

* `$table` is a table alias or name in a singular form.

**Returns**

Table descriptor as `stdClass`.

~~~ PHP
$result = $restAPI->Describe("Test");
print $result->columns[0]->name;
~~~

---

## Select {#select}

~~~ PHP
function Select(string $table, array $columns, string $filter = null, array<string> $sort = null) : array<array>
~~~

Retrieves the data from the table.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$columns` is an array of column names or aliases to retrieve.
* `$filter` is an optional string containing filter expression
* `$sort` is an optional array of column names to sort by.

**Returns**

An array of records, each record is represented as associative name=>value array.

~~~ PHP
$result = $restAPI->Select("Test", [ "Text", "Date" ], "not IsBlank([Date])");
print $result[0]["Text"];
~~~

---

## SelectTop {#selecttop}

~~~ PHP
function Select(string $table, int $top, int $skip, array $columns, string $filter = null, array<string> $sort = null) : array<array>
~~~

Version of Select() exteneded with top and skip parameters to enable paginated queries.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$top` is a number of records to retrieve.
* `$skip` is a number of records to skip prior to retrieval.
* `$columns` is an array of column names or aliases to retrieve.
* `$filter` is an optional string containing filter expression
* `$sort` is an optional array of column names to sort by.

**Returns**

An array of records, each record is represented as associative name=>value array.

~~~ PHP
$result = $restAPI->Select("Test", 10, 30, [ "Text", "Date" ], "not IsBlank([Date])");
print $result[0]["Text"];
~~~

---

## Retrieve {#retrieve}

~~~ PHP
function Retrieve(string $table, array<string> $columns, array<int> $ids) : array<array>
~~~

Retrieves records' data given their internal IDs.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$columns` is an array of column names or aliases to retrieve.
* `$ids` array of record ids.

**Returns**

An array of records, each record is represented as associative name=>value array.

~~~ PHP
$result = $restAPI->Retrieve("Test", [ "Text", "Date" ], [1, 2, 3]);
print $result[0]["Text"];
~~~

---

## RetrieveByKey {#retrievebykey}

~~~ PHP
function RetrieveByKey(string $table, array<string> $columns, array<int> $ids) : array<array>
~~~

Retrieves records' data by the value of record's key

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$columns` is an array of column names or aliases to retrieve.
* `$key` array of record key values.

**Returns**

An array of records, each record is represented as associative name=>value array.

~~~ PHP
$result = $restAPI->RetrieveByKey("Test", [ "Text", "Date" ], [ "1" , "2" , "3" ]);
print $result[0]["Text"];
~~~

---

## Create/Update/Upsert {#upsert}

~~~ PHP
function Create(string $table, array<array> $data, bool $no_workflow = null) : array<stdClass>
function Update(string $table, array<array> $data, string $match = null, bool $no_workflow = null) : array<stdClass>
function Upsert(string $table, array<array> $data, string $match = null, bool $no_workflow = null) : array<stdClass>
~~~

Set of three functions to manage records in the database. The difference is in behavior when record is (not) found.

Create will report error if record does exist.
Update will report error if record does not exist.
Upsert will search and update existing record or create new one.

Search is performed based on internal record's id under "@row.id" key, if present, or based on a value of key column or a column specified by `$match` parameter.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$data` is an array of associative arrays.
* `$match` is a alias or name of the column with unique values in it. Optional, `null` indicates key column. 
   
   Update and Upsert methods will try to search for a record with the value in match column equal passed data. If record is found, it will be updated. If record is not found, Update record will report 404 status, while Upsert method will create new record.

* `$no_workflow` allows to override "no-workflow" setting on a per-call basis.

**Returns**

An array of operation status descriptors - an `stdClass` containing the following fields:

* `status` - operation result, integer, resembles HTTP status codes: 200 - OK, 404 - Not found, etc.
* `id` - internal id of a record request.
* `key` - value of key column requested.
* `errors` - an array of `stdClass`
  * `status` - operation result, as above.
  * `code` - error message code.
  * `message` - error message.
  * `source` - optional error hint. 

~~~ PHP
// Creates two records
$result = $restAPI->Create("Test", [ 
	[
		"Text" => "Record Created #1",
		"Number" => 1,
		"Checkbox" => true,
		"Date" => time(),
		"Time" => time(),
		"Timestamp" => time(),
		"User" => "test@test.com",
		// add file from content
		"File" => TeamDesk\HttpContent::fromFile("./logo.png", "image/png")
	], 
	[
		"Text" => "Record Created #2"
		// rest of the fields will be assigned with their default values
	] 
]);

print $result[0]->status >= 400 ? "ERROR: {$status[0]->errors[0]->message}" : "OK";
~~~

---

## Delete {#delete}

~~~ PHP
function Delete(string $table, array<int> $ids, bool $no_workflow = null) : array<array>
~~~

Deletes records given their internal IDs.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$ids` array of record ids.
* `$no_workflow` allows to override "no-workflow" setting on a per-call basis.

**Returns**

An array of operation status descriptors - an `stdClass` containing the following fields:

* `status` - operation result, integer, resembles HTTP status codes: 200 - OK, 404 - Not found, etc.
* `id` - internal id of a record request.
* `key` - value of key column requested.
* `error` - error descriptor as `stdClass`
  * `status` - operation result, as above.
  * `code` - error message code.
  * `message` - error message.
  * `source` - optional error hint. 

~~~ PHP
$result = $restAPI->Delete("Test", [1, 2, 3]);
print $result[0]->status >= 400 ? "ERROR: {$status[0]->error->message}" : "OK";
~~~

---

## DeleteByKey {#deletebykey}

~~~ PHP
function DeleteByKey(string $table, array $keys, string $match = null, bool $no_workflow = null) : array<array>
~~~

Deletes records given their internal IDs.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$key` array of record key's values.
* `$no_workflow` allows to override "no-workflow" setting on a per-call basis.

**Returns**

An array of operation status descriptors - an `stdClass` containing the following fields:

* `status` - operation result, integer, resembles HTTP status codes: 200 - OK, 404 - Not found, etc.
* `id` - internal id of a record request.
* `key` - value of key column requested.
* `error` - error descriptor as `stdClass`
  * `status` - operation result, as above.
  * `code` - error message code.
  * `message` - error message.
  * `source` - optional error hint. 

~~~ PHP
$result = $restAPI->DeleteByKey("Test", [ "1" , "2" , "3" ]);
print $result[0]->status >= 400 ? "ERROR: {$status[0]->error->message}" : "OK";
~~~

---

## Document {#document}

~~~ PHP
function Document(string $table, string $document, array<int> $ids) : TeamDesk\HttpContent
~~~

Renders the document.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$document` is a document alias or name.
* `$ids` array of record ids.

**Returns**

TeamDesk\HttpContent object wrapping content headers and file data.

~~~ PHP
$result = $restAPI->Document("Test", "SampleDoc", [1, 2, 3]);
// dump file to response
$result->passthru(/*withHeaders:*/true);
~~~

---

## Updated {#updated}

~~~ PHP
function Updated(string $table, $from = null, $to = null) : array<stdClass>
~~~

Retrieves the list of updated records

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$from` - optional start date
* `$to` - optional end date

**Returns**

An array of `stdClass` with the following properties:

* `@row.id` - internal record's id.
* `key` - value of record's key.
* `created` - date and time the record was created
* `modified` - date and time the record was modified

~~~PHP
$result = $restAPI->Updated("Test", "2010-01-01T00:00:00");
print "id: {$result[0]->{"@row.id"}}, modified: {$result[0]->modified}";
~~~

---

## Deleted {#deleted}

~~~ PHP
function Deleted(string $table, $from = null, $to = null) : array<stdClass>
~~~

Retrieves the list of deleted records

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$from` - optional start date
* `$to` - optional end date

**Returns**

An array of `stdClass` with the following properties:

* `@row.id` - internal record's id.
* `deletedby` - string identifying the user.
* `deleted` - date and time the record was deleted

~~~PHP
$result = $restAPI->Updated("Test", "2010-01-01T00:00:00");
print "id: {$result[0]->{"@row.id"}}, deleted: {$result[0]->deleted}";
~~~

---

## Attachments {#attachments}

~~~PHP
function Attachments(string $table, string $column, int $id, int $revisions = null) : array<stdClass>
~~~

Retrieves attachment revision information.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$column` is a column alias or name.
* `$id` is an internal id of the record.
* `$revisions` is a number of revisions to retrieve.

**Returns**

Array of `stdClass` - attachment revision descriptors.

---

## AttachmentsByKey {#attachmentsbykey}

~~~PHP
function AttachmentsByKey(string $table, string $column, $key, int $revisions = null) : array<stdClass>
~~~

Retrieves attachment revision information.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$column` is a column alias or name.
* `$key` is the value of the key column.
* `$revisions` is a number of revisions to retrieve.

**Returns**

Array of `stdClass` - attachment revision descriptors.

---

## Attachment {#attachment}

~~~PHP
function Attachment(string $table, string $column, int $id, $revision = 0) : TeamDesk\HttpContent
~~~

Retrieves attachment.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$column` is a column alias or name.
* `$id` is an internal id of the record.
* `$revision` is either attachment's revision number, attachment's guid or a content of attachment column - in latter case we'll extract guid for you.

**Returns**

TeamDesk\HttpContent object wrapping content headers and file data.

~~~PHP
$row = $restAPI->Retrieve("Test", ["File"], [1])[0];
$result = $restAPI->Attachment("Test", "File", $row["@row.id"], $row["File"]);
header("Content-Type: " . $result->getHeader("Content-Type"));
$result->passthru();
~~~

---

## AttachmentByKey {#attachmentbykey}

~~~PHP
function AttachmentByKey(string $table, string $column, $key, $revision = 0) : TeamDesk\HttpContent
~~~

Retrieves attachment.

**Parameters**

* `$table` is a table alias or name in a singular form.
* `$column` is a column alias or name.
* `$key` is the value of the key column.
* `$revision` is either attachment's revision number, attachment's guid or a content of attachment column - in latter case we'll extract guid for you.

**Returns**

TeamDesk\HttpContent object wrapping content headers and file data.

~~~PHP
$row = $restAPI->Retrieve("Test", ["File"], [1])[0];
$result = $restAPI->Attachment("Test", "File", $row["@row.id"], $row["File"]);
header("Content-Type: " . $result->getHeader("Content-Type"));
$result->passthru();
~~~

## getLastRequest {#getLastRequest}

~~~ PHP
function getLastRequest() : TeamDesk\HttpRequest
~~~

**Returns**

Object-oriented wrapper for the data sent to TeamDesk.
  
---

## getLastResponse {#getLastResponse}

~~~ PHP
function getLastResponse() : TeamDesk\HttpResponse
~~~

**Returns**

Object-oriented wrapper for the data received from TeamDesk.

---

## dump {#dump}

~~~ PHP
function dump() : string
~~~

**Returns**

Last request and response formatted as a string.

