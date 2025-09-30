# Misc WP-CLI Commands

Collection of smallish, simple commands.

## `wp network list`

List networks. Outputs `wp_site` table.

```
$ wp network list
+----+-------------+------+
| id | domain      | path |
+----+-------------+------+
| 1  | example.com | /    |
+----+-------------+------+
```

## `wp site update`

Update domain/path values in `wp_blogs` table by blog ID.

```
$ wp site update 1 --domain=newdomain.com
```

## `wp cron-control debug`

Helps identify [cron-contol](https://github.com/Automattic/Cron-Control) entry issues.

```
$ wp cron-control debug
# example output truncated
+-----+--------+----------------------------------+----------------------------+-------------------------+----------------------------------------+
| ID  | action | instance                         | args                       | args_ok                 | match                                  |
+-----+--------+----------------------------------+----------------------------+-------------------------+----------------------------------------+
| 123 | action | 49a3696adf0fbfacc12383a2d7400d51 | a:1:{s:3:"foo";s:3:"baz";} | ✅ args can unserialize | ❌ WARNING: args do not match instance |
+-----+--------+----------------------------------+----------------------------+-------------------------+----------------------------------------+
```

## `wp wp_mail test`

Send a test email and report success/failure.

## `wp serialized-check`

Attempts to validate serialization of a string (very basic, inner strings only).

```
$ wp serialized-check 'a:1:{s:3:"foo";s:3:"bar";}'
Success: String can unserialize

$ wp serialized-check 'a:1:{s:3:"foo";s:4:"bar";}'
Found `bar` ( actual: 3 expected: 4 )

$ wp serialized-check  'a:1:{s:3:"foo";s:2:"bar";}' --repair
Found `bar` ( actual: 3 expected: 2 )
Success: Repaired value below. Be sure to update database as needed.
a:1:{s:3:"foo";s:3:"bar";}
```

## `wp post cache check`

Compares db and object cache values.

## `wp delete-file`

Delete file from VIP File System

## `wp find-by-path`

Find attachment by path

## `wp cap-compare`

Compare capabilities between user roles

```
$ wp cap-compare --roles=administrator,editor,author --caps=manage_options,publish_pages,unfiltered_html,publish_posts
+-----------------+---------------+--------+--------+
| cap             | Administrator | Editor | Author |
+-----------------+---------------+--------+--------+
| manage_options  | X             |        |        |
| publish_pages   | X             | X      |        |
| unfiltered_html | X             | X      |        |
| publish_posts   | X             | X      | X      |
+-----------------+---------------+--------+--------+
```
