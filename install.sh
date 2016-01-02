#!/bin/bash

# Get path of repo
REPO=$(pwd)

# Ubuntu requires changes in ~/.bashrc
if [ -f "~/.bashrc" ]; then
    BASH="~/.bashrc"
fi

# Mint requires chances in /etc/bash.bashrc
if [ -f "/etc/bash.bashrc" ]; then
    BASH="/etc/bash.bashrc"
fi

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

# Reload bashrc
. $BASH
