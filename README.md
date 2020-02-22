Backdrop Weekly Workers (bw-workers)
====================================

Some helper functions to automate some bw tasks.

get-releases.php
----------------

Move through the `backdrop-contrib/*` repos and determine if there are new releases
since a given `$date`.

Usage:

```bash
lando php get-releases.php 2020-02-13T20:08:20Z
```

If you need/want to do some testing w/out hitting 20 some odd pages of releases.
Pass in testing `TRUE` flag like so:

```bash
lando php get-releases 2020-02-13T20:08:20Z TRUE
```

that will just hit the first page of releases via GitHub API.

get-tweets.php
--------------

This file will hit the Twitter API and search for tweets w/ `backdropcms` in them.

usage:

```bash

```
