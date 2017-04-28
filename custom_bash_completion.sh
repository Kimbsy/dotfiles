# Custom autocomplete functions.

_gbdel() {

  local cur prev opts
  COMPREPLY=()
  cur="${COMP_WORDS[COMP_CWORD]}"
  prev="${COMP_WORDS[COMP_CWORD-1]}"

  opts=$(git branch | cut -c 3-)

  COMPREPLY=( $(compgen -W "${opts}" -- ${cur}) )
  return 0
}
complete -F _gbdel gbdel
