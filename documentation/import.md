# Import

The API provides an import method.

You need the URL to your installation and api file.
You need the token. Can be created in user management and has a lifespan.
You need the information which collection to import to and what data can be imported.

A working php example with example data-crow data can be found here:
```
http://91.132.146.200:3000/Banana/bibliotheca-tools/src/master/data-crow/run-import-to-bibliotheca.php
http://91.132.146.200:3000/Banana/bibliotheca-tools/src/master/data-crow/README
```

Info which data can be imported in example collation #1
```
{
  "message": "API POST and FILES data information for collection: Movies",
  "status": 200,
  "data": {
    "title": {
      "input": "string 128"
    },
    "description": {
      "input": "string 64"
    },
    "content": {
      "input": "mysql text"
    },
    "rating": {
      "input": "One of 0/10,2/10,3/10,4/10,5/10,6/10,7/10,8/10,9/10,10/10"
    },
    "actors": {
      "input": "string 64"
    },
    "directors": {
      "input": "string 64"
    },
    "genres": {
      "input": "string 64"
    },
    "runtime": {
      "input": "string 128"
    },
    "countries": {
      "input": "string 64"
    },
    "languages": {
      "input": "string 64"
    },
    "year": {
      "input": "mysql year"
    },
    "imdbrating": {
      "input": "string 128"
    },
    "viewcount": {
      "input": "string 128"
    },
    "storage": {
      "input": "string 64"
    },
    "tag": {
      "input": "string 64"
    },
    "category": {
      "input": "string 64"
    },
    "coverimage": {
      "input": "One file in $_FILES[uploads] of post"
    },
    "attachment": {
      "input": "Multiple in $_FILES[uploads] of post"
    }
  }
}
```

Array structure to be send as POST with curl to: `api.php?&authKey=API_TOKEN&collection=COLLECTION_ID`

```
array(15) {
    ["actors"]=> string(52) "Amber Heard,Gary Oldman,Harrison Ford,Liam Hemsworth"
    ["countries"]=> string(20) "France,United States"
    ["content"]=> string(628) "The high stakes thriller Paranoia takes us deep behind the scenes of global success to a deadly world of greed and deception. The two most powerful tech billionaires in the world (Harrison Ford and Gary Oldman) are bitter rivals with a complicated past who will stop at nothing to destroy each other. A young superstar (Liam Hemsworth), seduced by unlimited wealth and power falls between them, and becomes trapped in the middle of the twists and turns of their life-and-death game of corporate espionage. By the time he realizes his life is in danger, he is in far too deep and knows far too much for them to let him walk away." ["description"]=> string(125) "The high stakes thriller Paranoia takes us deep behind the scenes of global success to a deadly world of greed and deception."
    ["directors"]=> string(14) "Robert Luketic"
    ["genres"]=> string(14) "Drama,Thriller"
    ["title"]=> string(8) "Paranoia"
    ["languages"]=> string(7) "English"
    ["coverimage"]=> object(CURLFile)#356 (3) { ["name"]=> string(87) "import/movie/movies-export_images/00330b7f-5df8-49fa-ad79-2d1eb8ebb38c_PictureFront.jpg" ["mime"]=> string(0) "" ["postname"]=> string(0) "" }
    ["rating"]=> string(4) "6/10"
    ["runtime"]=> string(0) ""
    ["tag"]=> string(9) "Storage B"
    ["year"]=> string(4) "2013"
    ["attachment[0]"]=> object(CURLFile)#343 (3) { ["name"]=> string(87) "import/movie/movies-export_images/0efb720d-d9e5-406b-aa42-e356f9c544e9_PictureFront.jpg" ["mime"]=> string(0) "" ["postname"]=> string(0) "" }
    ["attachment[1]"]=> object(CURLFile)#342 (3) { ["name"]=> string(87) "import/movie/movies-export_images/3edf49eb-0270-456d-a15d-e5ddcba302ed_PictureFront.jpg" ["mime"]=> string(0) "" ["postname"]=> string(0) "" }
}
```
