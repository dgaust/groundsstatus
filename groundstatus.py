#! /usr/bin/env python

try: 
    from BeautifulSoup import BeautifulSoup
except ImportError:
    from bs4 import BeautifulSoup
import urllib
import json
import ftplib
import datetime

def createjson():
    time_run = datetime.datetime.now()
    #time_run = time_run.isoformat()
    time_run = time_run.strftime('%d %b %Y, %-I:%M %p')
    url = "https://wollongong.nsw.gov.au/explore/sport-and-recreation/sportsgrounds"
    print(url)
    f = urllib.urlopen(url)
    html = f.read()
    parsed_html = BeautifulSoup(html, "lxml")
    groundlist = []
    li = parsed_html.find('table', {'summary': 'SportsGrounds '})
    for table_row in li:
        cells = table_row.findAll('td')
        try:
           if len(cells) > 0:
              park_name = cells[0].text.strip()
              park_status = cells[4].text.strip()
              park_comment = cells[3].text.strip()
              park = {'park_name': park_name.encode('utf-8'), 'park_status': park_status.encode('utf-8'), 'park_comment': park_comment.encode('utf-8'), 'updated' : time_run}
              groundlist.append(park)
           else:
              print "no cells"
          # ftpupload()
        except:
           print('error')
    with open('test.json', 'wb') as outfile:
         json.dump(groundlist, outfile)
 
    ftpupload()
def ftpupload():
    try:
       session = ftplib.FTP('ftp.russellvalefootball.com', 'dgaust@russellvalefootball.com', 'Zcl217Asia2002')
       file = open('test.json', 'rb')
       session.storbinary('STOR /public_html/test.json', file)
       file.close()
       session.quit()
    except:
       print('ftp error')

createjson()
