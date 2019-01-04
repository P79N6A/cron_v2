#*coding=utf-8
'''
Created on 2017年7月18日

@author: hailin3
'''
import md5,types
from lcs_task.config import INNER_SIGN_KEY
class Sign:
    def __init__(self):
         pass
    #字符串编解码处理  
    def smart_str(self,s, encoding='utf-8', strings_only=False, errors='strict'):  
        if strings_only and isinstance(s, (types.NoneType, int)):  
            return s  
        if not isinstance(s, basestring):  
            try:  
                return str(s)  
            except UnicodeEncodeError:  
                if isinstance(s, Exception):  
                    return ' '.join([self.smart_str(arg, encoding, strings_only,  
                            errors) for arg in s])  
                return unicode(s).encode(encoding, errors)  
        elif isinstance(s, unicode):  
            return s.encode(encoding, errors)  
        elif s and encoding != 'utf-8':  
            return s.decode('utf-8', errors).encode(encoding, errors)  
        else:  
            return s  
    # 对数组排序并除去数组中的空值和签名参数  
    # 返回数组和链接串  
    def params_filter(self,params):  
        ks = params.keys()  
        ks.sort()  
        newparams = {}  
        prestr = ''  
        for k in ks:  
            v = params[k]  
            k = self.smart_str(k, 'utf-8')  
            if k not in ('sign','sign_type') and v != '':  
                newparams[k] = self.smart_str(v, 'utf-8')  
                prestr += '%s=%s&' % (k, newparams[k])  
        prestr = prestr[:-1]  
        return newparams, prestr  
      
      
    # 生成签名结果  
    def build_mysign(self,prestr, key, sign_type = 'MD5'):  
        if sign_type == 'MD5':              
            m1 = md5.new()   
            m1.update(prestr + INNER_SIGN_KEY)   
            return m1.hexdigest()   
        return ''  