############################################
# script to test command line validation
# 
# Usage :
#  ./testscript NUM_USERS
#  
############################################

echo ''
echo '# Testing command line validation:'
echo '#'

# go up a dir
cd ..

# compile C++ program to create import CSV
echo '# compiling import file generator...'
echo '#'

g++ createImportFile.cpp -o createImportFile

# run it and echo the output to /tmp/import.csv
echo '# creating import file...'
echo '#'

# ./createImportFile "$1" > /tmp/import.csv

# go back into site dir (quietly)
cd - > /dev/null

# run the drush command
echo '# validating...'
echo '#'

drush si-val test.csv csv input employee 0 0

# this gets done automatically
echo '# creating issue log...'
echo '#'

echo '# displaying log file contents:'
echo ''

cat /tmp/log.txt

echo ''
echo ''
