#*coding=utf-8
"""
圈子相关task   
"""
from __future__ import absolute_import
import os
import sys
sys.path.append(os.path.abspath('%s/..' % sys.path[0]))

from lcs_task import app
from lcs_task.comment.Comment import Comment

@app.task
def sendToCircle(discussion_id, discussion_type, content, puid):    
    try:
        c = Comment()
        result = c.sendCircleComment(discussion_id, discussion_type, content, puid)        
        return result
    except Exception,e:        
        return e
    

if __name__ == "__main__":
    sendToCircle(1, 2, "发观点", '2318006357')