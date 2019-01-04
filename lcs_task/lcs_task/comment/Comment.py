#*coding=utf-8
'''
Created on 2017年7月18日

@author: hailin3
'''
import urllib2
import urllib
from lcs_task.libs.urlsign import Sign
from lcs_task.models.user import User
from lcs_task.models.circle import Circle

class Comment:
    def __init__(self):
        pass
    
    def getContent(self,content):
        dict = {"view":"发布观点","ask":"回答问题","plan":"发布计划"}
        if not dict.has_key(content):
            return dict['view']
        return dict[content]
    
    def sendCircleComment(self,discussion_id,discussion_type,content,puid):
        user = User()
        circle = Circle()
        uid = user.getUidIndex(puid)
        user.destroy()
        circleinfo = circle.getCircleInfo(puid, 0)
        circle.destroy()
        if circleinfo == None:
            raise Exception("找不到圈子信息，理财师id：%s"%puid) 
        url = "http://hailin3.sina.com.cn/inner/balaComment"
        body_value = {
                      "grp": "circle",
                      "cmn_type": "71",
                      "relation_id":circleinfo['id'],
                      "content":self.getContent(content),
                      "discussion_type":discussion_type,
                      "discussion_id":discussion_id,
                      "login_uid":uid,
                      "login_u_type":2                     
                      }
        sign = Sign()
        params,paramstr = sign.params_filter(body_value)
        params['url_sign'] = sign.build_mysign(paramstr, '11')                
        body_value  = urllib.urlencode(params)
        request = urllib2.Request(url, body_value)
        request.add_header('Referer','http://licaishi.sina.com.cn/admin2/richer/ps/circle/myCircle.html')        
        result = urllib2.urlopen(request ).read()
        return result    
        
if __name__ == '__main__':
    c = Comment()
    c.sendCircleComment( 1, 2, '发布观点', 171429380);
        
    