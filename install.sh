#!/bin/bash

# Get location of bash config file from args
if [ -z $1 ]; then
    echo "ERROR -- Config file destination not specified."
    echo "usage:"
    echo "       ./install.sh <path_to_bashrc>"
fi
BASH=$1

# Get path of repo
REPO=$(pwd)

# Strings to put in bash file
RESOURCE="alias resource='source $BASH'"
FUNCTIONS=". $REPO/functions.sh"
ALIASES=". $REPO/aliases.sh"

if ! grep -q "$RESOURCE" $BASH; then
    echo "" >> $BASH
    echo "# Reload source of bash" >> $BASH
    echo "$RESOURCE" >> $BASH
fi

if ! grep -q "$FUNCTIONS" $BASH; then
    echo "" >> $BASH
    echo "# Functions from Kimbsy/dotfiles repo." >> $BASH
    echo "$FUNCTIONS" >> $BASH
fi

if ! grep -q "$ALIASES" $BASH; then
    echo "" >> $BASH
    echo "# Aliases from Kimbsy/dotfiles repo." >> $BASH
    echo "$ALIASES" >> $BASH
fi

# Remind to reload bash config.
echo "Config updated, please run 'source $1'"
