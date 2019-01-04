#*coding=utf-8
'''
Created on 2017年7月18日

@author: hailin3
'''
from lcs_task import db_r_pool
from lcs_task.db.mysql import Mysql
class User:
    def __init__(self):
        self._db_r = Mysql(db_r_pool.connection())
    
    def getUidIndex(self,suid):
        if suid == None:
            return 0
        sql = "select id from lcs_user_index where s_uid='%s'" % suid        
        info = self._db_r.getOne(sql)        
        if info == None:
            return 0
        return info['id']
            
    def destroy(self):
        self._db_r.dispose()
        