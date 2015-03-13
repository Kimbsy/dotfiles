#include <iostream>
#include <string>

using namespace std;

/**
 * Create user import files
 *
 * Usage:
 *   ./createImportFile > importUsers.csv
 */
int main() {
 
 // create header
 cout << "\"Status\",\"First name\",\"Last name\",\"Employee ID\",\"Primary workgroup\",\"Contributing workgroup\",\"Email address\",\"Primary job role\",\"Other job roles\",\"Use Reviews\",\"Default reviewer employee ID\",\"Use Learning\",\"Role and My Development mentor employee ID\",\"Start date\",\"Leaving date\",\"Timezone\",\"Advanced user\",\"Use Goals\"" << endl;


 // make a lot of users (one hundred thousand)
 for (int i = 0; i < 100; i++)
 {
  // output info
  cout << "1,\"Joe\",\"Bloggs\",\"ExUser" << i << "\",\"IT Systems and Development\",,\"dave.kimber+" << i << "@simitive.com\",\"Developer\",,1,\"SIM001\",1,\"SIM001\",01/01/1970,15/05/2015,\"Europe/London\",,1" << endl;
 } 
}
