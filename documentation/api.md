Small API to get information and add entries.

# Access

`api.php?QUERY_PARAMETERS`

# Parameters

ID of a collection `collection=NUMBER`

Get the latest 10 for given collection `p=list`
    
POST call to add a new entry to given collection. `p=add&collection=NUMBER&authKey=API_AUTH_TOKEN`
See `p=addInfo` for the details which info is needed in the add call.  More information can be found in import.md

Describes how the data in the POST add call should be formatted. `p=addInfo&collection=NUMBER`
The JSON info in the data field, tells which fields are available and in which format the value
is accepted. Expected is a curl call with an array as payload

# Response

The result is json
```
{
    "message": "Message as string",
    "status": INTEGER based on HTTP_STATUS CODE
    "data": {}
}
```
