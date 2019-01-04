#*coding=utf-8
'''
Created on 2017年7月4日

@author: hailin3
'''
import time
import datetime
import urllib2
"""
关闭过期订单
"""
class CloseOrder:    
    def __init__(self,db_w,db_r):
        self._db_r = db_w
        self._db_w = db_r
        
    def handle(self):
        list = self.getExpireOrder()
        if list == None:
            return "no expire order"
        for item in list:            
            self.closeOneOrder(item['order_no'], item['uid'], item['relation_id'], item['type'])
            break
        self._db_w.dispose()
        self._db_r.dispose()
        return "success"
    """
    关闭订单
    @param order_no:订单id
    @param uid: 用户id
    @param relation_id:关联id
    @param type:订单类型
    @return:     
    """
    def closeOneOrder(self,order_no,uid,relation_id,type):        
        print order_no
        if order_no == None:
            return
        nowtime = time.strftime("%Y-%m-%d %H:%M:%S")
        try:            
            up_sql = "update lcs_orders set status=%s where order_no=%s"            
            self._db_w.update(up_sql,('-1',order_no))
            couponid = self.getCouponId(uid, order_no)
            if couponid != 0:
                c_sql = "update lcs_user_coupon set order_no=%s,status=%s where order_no=%s and uid=%s and status=%s"
                self._db_w.update(c_sql,('',0,order_no,uid,1))
                reduce_sql = "update lcs_coupon set amount_use=(amount_use-1) where coupon_id=%d" % couponid
                self._db_w.update(reduce_sql)
            self.saveOrdersRecord(order_no, 'system', 0, 'close_orders', '关闭订单')
            q_row = None
            if type == 11:
                q_sql = "update lcs_ask_question set status=%s,u_time=%s where id=%s"
                q_row = self._db_w.update(q_sql,('-1',nowtime,relation_id))                            
            self._db_w.end()
            if q_row != None and q_row != 0:                
                urllib2.Request("http://i.licaishi.sina.com.cn/cacheApi/actionMyQuestions?uid=%s&type=1" % uid)
        except Exception,e:
            print e        
            self._db_w.end("rollback")    
    """
    保存订单关闭记录
    """
    def saveOrdersRecord(self,order_no,uid,u_type,oper_type,oper_note):
        nowtime = time.strftime("%Y-%m-%d %H:%M:%S")
        in_sql = "insert into lcs_orders_record (order_no,uid,u_type,oper_type,oper_note,c_time,u_time) values (%s,%s,%s,%s,%s,%s,%s)"            
        return self._db_w.insertOne(in_sql,(order_no,uid,u_type,oper_type,oper_note,nowtime,nowtime))
    """
    获取订单优惠券id
    """
    def getCouponId(self,uid,order_no):
        sql = "select coupon_id from lcs_user_coupon where uid=%s and order_no=%s"        
        coupon = self._db_r.getOne(sql,(uid,order_no))
        if coupon == None:
            return 0        
        return coupon['coupon_id']
    """
    获取过期列表
    """
    def getExpireOrder(self):        
        expire_time = (datetime.datetime.today() - datetime.timedelta(hours=23)).strftime("%Y-%m-%d %H:%M:%S")        
        sql = "SELECT id,order_no,type,uid,relation_id FROM lcs_orders WHERE status=%s AND c_time<%s"
        return self._db_r.getAll(sql,('1',expire_time))
        
if __name__ == '__main__':
    from lcs_task import db_w_pool,db_r_pool
    from lcs_task.db.mysql import Mysql
    db_w = Mysql(db_w_pool.connection())
    db_r = Mysql(db_r_pool.connection())
    c = CloseOrder(db_w,db_r)
    c.handle()