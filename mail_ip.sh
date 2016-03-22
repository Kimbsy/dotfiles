echo $(ifconfig wlan0 | grep "inet addr" | awk -F: '{print $2}' | awk '{print $1}') | mail -s "RaspberryPi ip addr" lordkimber@gmail.com

