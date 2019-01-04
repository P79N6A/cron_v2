#*coding=utf-8
'''
Created on 2017年7月19日

@author: hailin3
'''
import urllib,urllib2

LCS_WEB_INNER_URL = 'http://i.licaishi.sina.com.cn'

def removeCacheForPublishView(puid,ind_id,quote_url):
    url = LCS_WEB_INNER_URL+'/cacheApi/addView'
    params = {"p_uid":puid,"ind_id":ind_id,"quote_url":quote_url}              
    body_value  = urllib.urlencode(params)
    request = urllib2.Request(url, body_value)            
    result = urllib2.urlopen(request ).read()
    return result

def removeCacheForUpdatePackage(puid,pkg_id=0):
    url = LCS_WEB_INNER_URL+'/cacheApi/plannerPkg'
    params = {"p_uid":puid}              
    body_value  = urllib.urlencode(params)
    request = urllib2.Request(url, body_value)            
    result = urllib2.urlopen(request ).read()    
    if pkg_id == 0:
        return result
    url = LCS_WEB_INNER_URL+'/cacheApi/package'
    params = {"pkg_id":pkg_id}              
    body_value  = urllib.urlencode(params)
    request = urllib2.Request(url, body_value)            
    result = urllib2.urlopen(request ).read()
    return result
