#!/bin/bash

# Check arg count
ARG_COUNT=1
if [ $# -ne $ARG_COUNT ]; then
    echo "Usage: ./`basename $0` <path_to_bashrc>"
    exit
fi

# Get location of bash config file from args
if [ -z $1 ]; then
    echo "ERROR -- Config file destination not specified."
    echo "Usage: ./`basename $0` <path_to_bashrc>"
fi

bashrc=$1

# get path of repo
repo=$(pwd)

# strings to put in bash file
resource="alias resource='source $bashrc'"
functions=". $repo/functions.sh"
aliases=". $repo/aliases.sh"

if ! grep -q "$resource" "$bashrc"; then
    echo "" >> "$bashrc"
    echo "# Reload source of bash" >> "$bashrc"
    echo "$resource" >> "$bashrc"
fi

if ! grep -q "$functions" "$bashrc"; then
    echo "" >> "$bashrc"
    echo "# Functions from Kimbsy/dotfiles repo." >> "$bashrc"
    echo "$functions" >> "$bashrc"
fi

if ! grep -q "$aliases" "$bashrc"; then
    echo "" >> "$bashrc"
    echo "# Aliases from Kimbsy/dotfiles repo." >> "$bashrc"
    echo "$aliases" >> "$bashrc"
fi

# Copy over autocompletion stuff.
sudo cp "$repo/custom_bash_completion.sh" /etc/bash_completion.d/custom_bash_completion
sudo chmod 644 /etc/bash_completion.d/custom_bash_completion

# Remind to reload bash config.
echo "Config updated, please run the following command:"
echo ""
echo ". $1 && . /etc/bash_completion.d/custom_bash_completion"
echo ""
