#!/bin/bash

vg_SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
source "$vg_SCRIPT_DIR/lib.trap.sh"

vg_mysql_location=$(which mysql)
echo "vg_mysql_location=$vg_mysql_location"

vg_mysql_version=$(sudo mysql1 --version)
echo "vg_mysql_version=$vg_mysql_version"