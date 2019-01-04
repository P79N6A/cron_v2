#*coding=utf-8
"""
订单相关schedules   
"""
from __future__ import absolute_import
import os
import sys
sys.path.append(os.path.abspath('%s/..' % sys.path[0]))

from lcs_task import app,db_w_pool,db_r_pool
from lcs_task.order.CloseExpireOrder import CloseOrder

from lcs_task.db.mysql import Mysql

@app.task
def closeExpireOrder():  
    """
    关闭超过23小时未支付的订单
    """  
    db_w = Mysql(db_w_pool.connection())
    db_r = Mysql(db_r_pool.connection())
    e = CloseOrder(db_w,db_r)
    return e.handle()
    

