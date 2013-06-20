This is the core suite of plugins for the PHP-FluxAPI.

The plugins are totally optional but provide a good starting point and already solve some basic requirements of a web application.

The plugins contain:

## Storage Adapters

- MySQL
- MongoDB (planned)
- SQLLite (planned)

## Models

- Node (simple content type with title and body)
- User (a simple user with username, email, password, ...)
- UserGroup (collects users in groups, has permissions)
- File (holds information about uploaded files)
- Permissions (planned)

## Formats

- xml
- json
- yaml
- html (in progress)
- bson (planned)

## FieldValidators

- Required
- Email
- Alias: creates URL-safe aliases, can also use another field's value to create the alias
- Url
- StripTags: removes tags
- MinLength (planned)
- MaxLength (planned)
- Min (planned)
- Max (planned)
- Date (planned)
- DateTime (planned)
- Time (planned)
- Type (planned)
- ...

## Controllers

- User: adds login/logout actions

## Permissions

- User: does nothing yet


## Various

- automaticly updating createdAt and updatedAt fields of any model extending the Plugins\FluxAPI\Model
