/*
 * Doing some defines to get rid of hardcoded numbers.
 */
#define GPSNUMBER 4 //amount of gps's to connect to give "real" data. Not sure if 4 is enough.
#define POSTINGDELAY 10000 // milliseconds. Delay for the wifi connect and posting.
#define IAMONLINE 15* 60000 //If device doesen't send db data in x'amount of time. This time tells when
                                // to force send timestamp to db, so page won't think device is offline.
#define LEDBLINK 500
#define DISTANCETOPOST 5 //Distance from last point needs to be higher than this. I think these are meters.

#include <TinyGPSPlus.h>
#include <SoftwareSerial.h>
#include <ESP8266WiFi.h>
#include <WiFiClient.h>
//#include <WiFiClientSecure.h> //perhaps this cause wemos circuit to slow, or i didn't know
//how to properly use it.

#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>
#include <ESP8266WiFiMulti.h>
/*
   some of the above includes may be removed. Not sure because of wifimulti testing
*/

TinyGPSPlus gps;
SoftwareSerial softwareserial(4, -1);


//enter here wifi name and password. testing multiwifi stuff
ESP8266WiFiMulti multi_wifi;
//wifi 1
const char *ssid1 = "SSID1";
const char *password1 = "PASSWORD1";
//wifi 2
const char *ssid2 = "SSID2";
const char *password2 = "PASSWORD2";
uint16_t connectTimeOutPerAP = 5000;

/*
   Here is the addres where esp8266 connects to, change this to
    your address. Leave /postgps.php to the end. It is the file
    that posts info from here to database. Unless you change
    the file name, it must be changed here aswell.
*/
const char *url = "http://yourwebaddress.com/postgps.php";

/*
 * Variables to delay the post for timerDelay amount
 * of time. So this will post to database only every
 * 10 seconds.
 */
unsigned long lastTime = 0;


float loc[2];
String latitude, longitude, gpsnumber;
float curLatitude, curLongitude;
unsigned long distanceToLastPoint = 10;
int gpsamount;

//have to use 2 bools. because gps own location.valid doesen't
//go back to false when it has once gone true.
bool gpsValid = true;
bool locationValid = false;
unsigned long timeFromLastPost = 0;


String onlineStatus = "online";
/*
 * Variables to blink light while software
 * serial is available
 */
unsigned long previousMillis = 0;
bool blinking = false;
int pin = 2;
int a = 0;


void setup()
{
  pinMode(pin, OUTPUT);
  // put your setup code here, to run once:
  Serial.begin(115200);
  softwareserial.begin(4800);
  Serial.println("NYT kaynnisty");

  /*
   * Add to multi_wifi access points what it scans. Will connect to strongest signal first.
   */
  multi_wifi.addAP(ssid1, password1);
  multi_wifi.addAP(ssid2, password2);
  delay(1000);
  
  //while(multi_wifi.run(connectTimeOutPerAP) != WL_CONNECTED)
  //{
   // Serial.print(".");
   // delay(1000);
 // }
 // Serial.println();
 // Serial.print("Connected to ");
 // Serial.println(WiFi.SSID());
 // Serial.print("IP ADdress: ");
 // Serial.println(WiFi.localIP());
}

void loop()
{

  digitalWrite(pin, blinking);
  // put your main code here, to run repeatedly:
  while (softwareserial.available())
  {   //led only blinks if softwareserial is available. debug purposes
      if(millis() - previousMillis >= LEDBLINK)
      {
       previousMillis = millis();
       blinking = !blinking;
      }
    gps.encode(softwareserial.read());
    if (gps.location.isValid() && !locationValid)
    {
      Serial.print("location: "); Serial.println(gps.location.isValid());
      locationValid = true;
    }

    //propably useless those printInt and printFloat funktions.
    int a = printInt(gps.satellites.value(), gps.satellites.isValid());
    //preventing to get any debug locations
    if (locationValid)
    {
      loc[1] = printFloat(gps.location.lng(), gps.location.isValid());
      loc[0] = printFloat(gps.location.lat(), gps.location.isValid());

    }
    //need to turn these to strings for db send.
    latitude = String(loc[0], 6);
    longitude = String(loc[1], 6);
    gpsnumber = String(a);
    gpsamount = a;

    //not sure if 4 gps's are enough for accurate data.
    // 3 for sure isn't accurate at all.
    //gpsValid bool will tell if we post to db every time
    //or just rarely, so site will know the device is still
    //online but with bad gps signal.
    if (gpsamount < GPSNUMBER && gpsValid)
    {
      gpsValid = false;

      Serial.println("gps signal is bad");
    }
    else if(gpsamount >= GPSNUMBER && !gpsValid)
    {
      gpsValid = true;
      Serial.println("Gps signaali ois jepa");
    }

  }
    /*
     * Force sends to db after certain amount of time has passed even
     * if there isn't good gps signal. So webpage can see if device
     * is offline.
     */
  if (millis() - timeFromLastPost > IAMONLINE)
  {
    /*
     * Will send onlineStatus offline to db, so db posting
     * php files knows what to do.
     */
    Serial.println("Posting just time and date so page doesen't think device is offline");
    onlineStatus = "offline";

    sendDb();

  }

  //propably could do without locationValid bool
  if(locationValid)
  {
    if(gpsValid)
    { 
        if ((millis() - lastTime) > POSTINGDELAY)
        {  
         distanceToLastPoint = (unsigned long)TinyGPSPlus::distanceBetween(loc[0], loc[1],
         curLatitude, curLongitude);
         Serial.println(); Serial.println();
         Serial.print("Distance to last point: "); Serial.println(distanceToLastPoint); 
         Serial.println(); Serial.println();
         if(distanceToLastPoint > DISTANCETOPOST)
         {    
          sendDb();
         }
         else
          Serial.println("Distance was not high enough, won't post now");
          
        lastTime = millis();
        }  
     }
   }
}

void sendDb()
{
    WiFiClient client;



    while(multi_wifi.run(connectTimeOutPerAP) != WL_CONNECTED)
    {
      Serial.print(".");
      delay(500);
    }
    if(multi_wifi.run() == WL_CONNECTED)
    {
      Serial.print("Connected to: "); Serial.println(WiFi.SSID());
      HTTPClient http;
      String postData;
      postData = "status=" + onlineStatus + "&gpsnumber=" + gpsnumber + "&latitude=" + latitude + "&longitude=" + longitude;
      http.begin(client, url); //request destination
      http.addHeader("Content-Type", "application/x-www-form-urlencoded"); //specify content-type header

      int httpCode = http.POST(postData); //send request
      String payload = http.getString(); //get the response payload
      Serial.print("Http code on: ");

      Serial.println(httpCode);
      Serial.println(payload);

      http.end();
        
      WiFi.disconnect();

      onlineStatus = "online";
      timeFromLastPost = millis();
      
      Serial.println();


      Serial.print("Loc[0] is: "); Serial.print(loc[0], 6); Serial.print(".  Loc[1] is: "); Serial.println(loc[1], 6);
      Serial.print("curloc[0] is: "); Serial.print(curLatitude, 6); Serial.print(". curLoc[1] is: ");
      Serial.println(curLongitude, 6);
          Serial.println();
      if(gpsValid)
      {
        curLatitude = loc[0];
        curLongitude = loc[1];
      }
    }

}

int printInt(unsigned long val, bool valid)
{
  if (valid)
  {
    return val;
  }
  else
  {
    return -2;
  }
}

float printFloat(float val, bool valid)
{
  if (valid)
  {
    return val;
  }
  else
    return -1;
}
