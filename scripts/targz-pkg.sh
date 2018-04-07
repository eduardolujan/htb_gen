#!/bin/bash
version=${1?Arg pckg-version missing}
git-tar-tree htb-gen-${version} htb-gen | gzip > packages/htb-gen-${version}.tar.gz
