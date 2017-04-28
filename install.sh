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

# Change some of the default settings.
sed -i "/#force_color_prompt=yes/c\force_color_prompt=yes" "$bashrc" # Force coloured prompt.
sed -i "/alias ll='ls -alF'/c\alias ll='ls -halF'" "$bashrc"         # Add the h flag to ll.
sed -i "/#shopt -s globstar/c\shopt -s globstar" "$bashrc"           # Turn on double star globbing.

# strings to put in bash file
resource="alias resource='source $bashrc'"
functions=". $repo/functions.sh"
aliases=". $repo/aliases.sh"
autocomplete=". /etc/bash_completion.d/custom_bash_completion"

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

if ! grep -q "$autocomplete" "$bashrc"; then
    echo "" >> "$bashrc"
    echo "# Autocomplete functions from Kimbsy/dotfiles repo." >> "$bashrc"
    echo "$autocomplete" >> "$bashrc"
fi

# Copy over autocompletion stuff.
sudo cp "$repo/custom_bash_completion.sh" /etc/bash_completion.d/custom_bash_completion
sudo chmod 644 /etc/bash_completion.d/custom_bash_completion

# Remind to reload bash config.
echo "Config updated, please run the following command:"
echo ""
echo ". $1"
echo ""

# Import default profile for Terminator.
mkdir -p "/home/kimbsy/.config/terminator/"
cp "$repo/config/terminator/config" "/home/kimbsy/.config/terminator/config"

# Import key bindings for Sublime Text.
mkdir -p "/home/kimbsy/.config/sublime-text-3/Packages/User"
cp "$repo/config/sublime/Default (Linux).sublime-keymap" "/home/kimbsy/.config/sublime-text-3/Packages/User/"

# Reminder to reload bash config.
RED='\033[0;31m'
NC='\033[0m' # No Color
printf "${RED}Config updated, please run 'source $bash_config'${NC}\n"
