#! /usr/bin/env python

from lxml import html
import requests
import json
import ftplib
import datetime

def createjson():
   time_run = datetime.datetime.now()
   time_run = time_run.strftime('%d %b %Y, %-I:%M %p')
   groundlist = []

   page = requests.get('https://www.wollongong.nsw.gov.au/explore/sport-and-recreation/sportsgrounds')
   tree = html.fromstring(page.content)
   allgroundnames = tree.xpath('//div[@class="sportsgrounds__info"]')
   
   alertstatus = tree.xpath('//div[@class="alert__message"]/text()')
   countofitems = len(alertstatus)
   if countofitems != 0: 
      comment = alertstatus[0]
      if countofitems > 2:
         comment = comment + "<br/>" + alertstatus[1]
   else:
      comment = 'No comments'
   
   i = 0
   for item in allgroundnames:
       grounds = item.xpath('//div[@class="sportsgrounds__name"]/a/text()')
       status = item.xpath('//div[@class="sportsgrounds__status"]/span[2]/text()')
       park_name = grounds[i].split(',')[0]
       park_status = status[i]
       park_comment = comment
       park = {'park_name': park_name, 'park_status': park_status, 'park_comment': park_comment, 'updated' : time_run}
       groundlist.append(park)
       i = i + 1

   with open('test.json', 'w') as outfile:
        json.dump(groundlist, outfile)

   ftpupload()

def ftpupload():
    try:
       session = ftplib.FTP_TLS('ftp.russellvalefootball.com', 'user', 'pass')
       file = open('test.json', 'rb')
       session.prot_p()
       session.storbinary('STOR /public_html/test.json', file)
       file.close()
       session.quit()
    except:
       print('ftp error')

createjson()
