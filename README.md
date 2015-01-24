# dotfiles
my .dotfiles contains useful and/or fun things to add to ~/.bashrc to make the terminal better

add these lines to ~/.bashrc

  # Add external files for functions and aliases
  test -r ~/Programs/dotfiles/functions.sh && . ~/Programs/dotfiles/functions.sh
  test -r ~/Programs/dotfiles/aliases.sh && . ~/Programs/dotfiles/aliases.sh