#*coding=utf-8
'''
Created on 2017年7月18日

@author: hailin3
'''
from lcs_task import db_r_pool
from lcs_task.db.mysql import Mysql
class Circle:
    def __init__(self):
        self._db_r = Mysql(db_r_pool.connection())
    
    def getCircleInfo(self,puid,ctype):       
        sql = "select * from lcs_circle where p_uid=%s and type=%s"
        info = self._db_r.getOne(sql,(puid,ctype))
        return info
    
    def destroy(self):
        self._db_r.dispose()
            
        