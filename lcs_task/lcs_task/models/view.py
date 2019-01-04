#*coding=utf-8
'''
Created on 2017年7月18日

@author: hailin3
'''
import time
from lcs_task import db_r_pool
from lcs_task.db.mysql import Mysql
class View:
    def __init__(self):
        self._db_r = Mysql(db_r_pool.connection())
    
    def updatePackageLastVid(self,pkg_id,viewid):
        nowtime = time.strftime("%Y-%m-%d %H:%M:%S")
        sql = "update lcs_package set v_id=%s,view_time=%s,view_num=view_num+1,u_time=%s,operate_time=%s where id=%s"
        return self._db_r.update(sql, (viewid,nowtime,nowtime,nowtime,pkg_id))
            
    def destroy(self):
        self._db_r.dispose()
        
if __name__ == '__main__':
    v = View()
    print v.updatePackageLastVid(1, 1)