#########################################################################
############################### FUNCTIONS ###############################
#########################################################################


# Easily extract all compressed file types
extract () {
   if [ -f "$1" ] ; then
       case "$1" in
           *.tar.bz2)   tar xvjf  "$1"    ;;
           *.tar.gz)    tar xvzf "$1"     ;;
           *.bz2)       bunzip2  "$1"     ;;
           *.rar)       unrar x  "$1"     ;;
           *.gz)        gunzip  "$1"      ;;
           *.tar)       tar xvf  "$1"     ;;
           *.tbz2)      tar xvjf  "$1"    ;;
           *.tgz)       tar xvzf  "$1"    ;;
           *.zip)       unzip  "$1"       ;;
           *.Z)         uncompress  "$1"  ;;
           *.7z)        7z x  "$1"        ;;
           *)           echo "don't know how to extract '$1'..." ;;
       esac
   else
       echo "'$1' is not a valid file."
   fi
}

# Play next episode of Game of Thrones from directory,
# then move it to watched directory
GoT () {
  cd Videos/GoT
  WATCHED=~/Videos/GoT/watched/

  NEXT=$(ls | head -1)
  echo "Watching $NEXT"

  mv $NEXT $WATCHED
  vlc -f "$WATCHED/$NEXT"
}

# Define a word using collinsdictionary.com
define() {
  curl -s "http://www.collinsdictionary.com/dictionary/english/$*" | sed -n '/class="def"/p' | awk '{gsub(/.*<span class="def">|<\/span>.*/,"");print}' | sed "s/<[^>]\+>//g"
}

# Get weather data for Bristol
weather() {
    key=58b5b5a99ae513c8
    echo BRISTOL:
    echo ''
    data=$(curl -s "http://api.wunderground.com/api/$key/forecast/q/UK/Bristol.json" | jq -r ['.forecast.txt_forecast.forecastday[] | [.title], [.fcttext], ["break"] | .[]'])
    echo $data | sed -e 's/[,]/\n/g' -e 's/[]"]/''/g' -e 's/[[]/''/g' -e 's/break/\n/g'
}

# Epoch time conversion
# can take either timestamp or date as param
# Usage:
#   epoch 140526899
#   epoch 22 june 2014
epoch() {
  TESTREG="[\d{10}]"
  if [[ "$1" =~ $TESTREG ]]; then
    # is epoch
    date -d @$*
  else
    # is date
    if [ $# -gt 0 ]; then
      date +%s --date="$*"
    else
      date +%s
    fi
  fi
}

# Recursively greps current directory and all subsequent directories 
# for commented lines matching clearly broken shit.
moag() {
  grep -IiRhP \
  "(\/\/|\*).*(todo|hack|quick|needs doing|sorry|lol|soz|shit|fuck|bastard|stupid|cunt|twat|terrible|horrible|awful|crappy|probably|bloody|broke|bollocks|hard[\s\-]code)" \
  --exclude-dir={node_modules,bower_components,public*,lib*,vendor,old} \
  --exclude={*.log,*.err,*.md,README*,jQuery*,*min.js} \
  all/modules/custom \
  | sed 's/^\s*/ - /' \
  | less
}

# play a beep through the speakers
# e.g.
#    alarm 800 200
alarm() {
  ( \speaker-test --frequency $1 --test sine )&
  pid=$!
  \sleep 0.${2}s
  \kill -9 $pid
}
alias beep='alarm 800 200 > /dev/null'

