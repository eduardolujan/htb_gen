#!/bin/bash
version=${1?Arg pckg-version missing}
cd packages && fakeroot alien -k -r htb-gen_${version}_all.deb 
