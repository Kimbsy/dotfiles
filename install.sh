#!/bin/bash

# Get location of bash config file from args.
if [ -z $1 ]; then
    echo "ERROR -- Config file destination not specified."
    echo "usage:"
    echo "       ./install.sh <path_to_bashrc>"
fi
bash_config=$1

# Get path of repo.
repo=$(pwd)

# Strings to put in bash file.
resource="alias resource='source $bash_config'"
functions=". $repo/functions.sh"
aliases=". $repo/aliases.sh"

# Change some of the default settings.
sed -i "/#force_color_prompt=yes/c\force_color_prompt=yes" "$bash_config" # Force coloured prompt.
sed -i "/alias ll='ls -alF'/c\alias ll='ls -halF'" "$bash_config"         # Add the h flag to ll.
sed -i "/#shopt -s globstar/c\shopt -s globstar" "$bash_config"           # Turn on double star globbing.

if ! grep -q "$resource" "$bash_config"; then
    echo "" >> "$bash_config"
    echo "# Reload source of bash" >> "$bash_config"
    echo "$resource" >> "$bash_config"
fi

if ! grep -q "$functions" "$bash_config"; then
    echo "" >> "$bash_config"
    echo "# Functions from Kimbsy/dotfiles repo." >> "$bash_config"
    echo "$functions" >> "$bash_config"
fi

if ! grep -q "$aliases" "$bash_config"; then
    echo "" >> "$bash_config"
    echo "# Aliases from Kimbsy/dotfiles repo." >> "$bash_config"
    echo "$aliases" >> "$bash_config"
fi

# Reminder to reload bash config.
RED='\033[0;31m'
NC='\033[0m' # No Color
printf "${RED}Config updated, please run 'source $bash_config'${NC}\n"
