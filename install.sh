#!/bin/bash

# Change some of the default settings.
sed -i "/#force_color_prompt=yes/c\force_color_prompt=yes" ~/.bashrc # Force coloured prompt.
sed -i "/alias ll='ls -alF'/c\alias ll='ls -halF'" ~/.bashrc         # Add the h flag to ll.
sed -i "/#shopt -s globstar/c\shopt -s globstar" ~/.bashrc           # Turn on double star globbing.

# Copy .bash_custom
cp .bash_custom ~/.bash_custom

# strings to put in bash file
custom="if [ -r ~/.bash_custom ]; then . ~/.bash_custom; fi"
resource="alias resource='source ~/.bashrc'"
autocomplete=". /etc/bash_completion.d/custom_bash_completion"

if ! grep -q "~/.bash_custom" ~/.bashrc; then
    echo "" >> ~/.bashrc
    echo "# Source ~/.bash_custom" >> ~/.bashrc
    echo "$custom" >> ~/.bashrc
fi

if ! grep -q "$resource" ~/.bashrc; then
    echo "" >> ~/.bashrc
    echo "# Reload source of bash" >> ~/.bashrc
    echo "$resource" >> ~/.bashrc
fi

if ! grep -q "$autocomplete" ~/.bashrc; then
    echo "" >> ~/.bashrc
    echo "# Autocomplete functions from Kimbsy/dotfiles repo." >> ~/.bashrc
    echo "$autocomplete" >> ~/.bashrc
fi

# Copy over autocompletion stuff.
sudo cp "custom_bash_completion.sh" /etc/bash_completion.d/custom_bash_completion
sudo chmod 644 /etc/bash_completion.d/custom_bash_completion

# Reminder to reload bash config.
echo "Config updated, please run 'source ~/.bashrc'"
