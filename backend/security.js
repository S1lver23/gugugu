function rateLimiter({windowMs,max,maxKeys=10000,clock=Date.now}) {
 const entries=new Map();let lastCleanup=0;
 return (req,res,next)=>{
  const now=clock();
  if(now-lastCleanup>60000){for(const [key,value] of entries)if(now-value.start>=windowMs)entries.delete(key);lastCleanup=now;}
  const key=req.ip || req.socket?.remoteAddress || 'unknown';let entry=entries.get(key);
  if(!entry || now-entry.start>=windowMs){
   if(!entry && entries.size>=maxKeys)return res.status(429).json({success:false,message:'服務忙碌，請稍後再試'});
   entry={start:now,count:0};entries.set(key,entry);
  }
  entry.count++;
  if(entry.count>max){res.set('Retry-After',String(Math.max(1,Math.ceil((windowMs-(now-entry.start))/1000))));return res.status(429).json({success:false,message:'嘗試次數過多，請稍後再試'});}
  next();
 };
}
function headers(req,res,next){
 res.set({'X-Content-Type-Options':'nosniff','X-Frame-Options':'DENY','Referrer-Policy':'no-referrer','Cache-Control':'no-store','Content-Security-Policy':"default-src 'none'; frame-ancestors 'none'"});next();
}
module.exports={rateLimiter,headers};
