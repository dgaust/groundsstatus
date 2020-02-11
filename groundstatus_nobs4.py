from lxml import html
import requests

page = requests.get('https://www.wollongong.nsw.gov.au/explore/sport-and-recreation/sportsgrounds')
tree = html.fromstring(page.content)
grounds = buyers = tree.xpath('//div[@title="portsgrounds__item"]/text()')

print 'Grounds: ', grounds
