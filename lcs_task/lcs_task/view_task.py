#*coding=utf-8
from __future__ import absolute_import
from lcs_task import app
import os
import sys
from lcs_task.comment.Comment import Comment
from lcs_task.libs.cacheapi import removeCacheForPublishView,removeCacheForUpdatePackage
from lcs_task.models.view import View
sys.path.append(os.path.abspath('%s/..' % sys.path[0]))

@app.task
def view_test():
    print "view_test"
    return "view"
@app.task
def save_view_after(vid,puid,pkg_id,ind_id,quote_url):
    #更新最后观点id、发到圈子、清缓存
    try:
        v = View()
        v.updatePackageLastVid(pkg_id, vid)
        v.destroy()
        c = Comment()
        result = c.sendCircleComment(vid, 2, 'view', puid)
        removeCacheForPublishView(puid,ind_id,quote_url)
        removeCacheForUpdatePackage(puid, pkg_id)        
        return result
    except Exception,e:        
        return e
    
    
