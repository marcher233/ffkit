<?php
/** 
 * 饭否操作类 
 * 
 * @package Wang Haosen
 * @author Wang Haosen
 * @version 1.0
   @writen at July 6, 2011  
 
   @说明 这个文件是为饭否写的一个PHP SDK
   @希望能给后来开发的人带来方便 ^_^
   
   @注意，这个文件使用时必须先引用同文件夹下的oauth.php 和 config.php
   @PS:没事来http://pagewant.com坐一坐~
 */ 
 
class FFClient 
{ 
    /** 
     * 构造函数 
     * @param mixed $akey 饭否给你的 APP KEY (Consumer_Key ) 
     * @param mixed $skey 饭否给你的 APP SECRET (Consumer_Secret_Key ) 
     * @param mixed $accecss_token OAuth认证返回的token 
     * @param mixed $accecss_token_secret OAuth认证返回的token secret 
     */ 
    function __construct( $akey , $skey , $accecss_token , $accecss_token_secret ) 
    { 
        $this->oauth = new OAuth( $akey , $skey , $accecss_token , $accecss_token_secret ); 
    } 
	
	

//////////////////////////////////////////消息相关////////////////////////////////////////////////////


    /** 
     * 显示随便看看的消息
	 * count是信息条数 
     */ 
    function public_timeline( $count = 20 ) 
    { 
        return $this->oauth->get('http://api.fanfou.com/statuses/public_timeline.json?count='.$count);
    } 

    /** 
     * 显示来自用户和好友的消息
	 * page 是页数，以下都是，不再说明
     */ 
    function friends_timeline($page = 1, $count = 20) 
    { 
		return $this->request_with_pager( 'http://api.fanfou.com/statuses/friends_timeline.json' , $page , $count ); 
    } 
	
    /** 
     * 显示用户的消息
     */ 
    function user_timeline($page = 1, $count = 20) 
    { 
		return $this->request_with_pager( 'http://api.fanfou.com/statuses/user_timeline.json' , $page , $count ); 
    } 

    /** 
     * 显示指定消息 通过消息ID
     */ 
    function show( $stauts_id ) 
    { 
		return $this->oauth->get( 'http://api.fanfou.com/statuses/show/'.$stauts_id.'.json'); 
    } 
	
    /** 
     * 显示发给当前用户的消息
     */ 
    function replies( $page =1, $count = 20) 
    { 
		return $this->request_with_pager( 'http://api.fanfou.com/statuses/replies.json' , $page , $count ); 
    }
	
    /** 
     * 最新 @用户的 
     * @param int $page 返回结果的页序号。 
     * @param int $count 每次返回的最大记录数（即页面大小），不大于200，默认为20。 
     * @return array 
     */ 
    function mentions( $page = 1 , $count = 20 ) 
    { 
        return $this->request_with_pager( 'http://api.fanfou.com/statuses/mentions.json' , $page , $count ); 
    } 


    /** 
     * 发表微博
     * @access public 
     * @param mixed $text 要更新的微博信息。 
     * @return array 
     */ 
    function update( $text , $replyto = false ) 
    { 
        $param = array(); 
        $param['status'] = $text;
        $param['mode'] = $lite; 
		if ( $replyto )
			$param['in_reply_to_status_id'] = $replyto;
	//	$param['source'] = $source; 
	//	$param['location'] = $location; 
        return $this->oauth->post( 'http://api.fanfou.com/statuses/update.json' , $param ); 
    }
    
    /** 
     * 发表图片微博 
     * @param string $text 要更新的微博信息。 
     * @param string $pic_path 要发布的图片路径,支持url。[只支持png/jpg/gif三种格式,增加格式请修改get_image_mime方法] 
     * @return array 
     */ 
    function upload( $text , $pic_path ) 
    {  
        $param = array(); 
        $param['status'] = $text; 
        $param['photo'] = '@'.$pic_path;
        
        return $this->oauth->post( 'http://api.fanfou.com/photos/upload.json' , $param , true ); 
    } 
	
    /** 
     * 转发微博
     * @param mixed $text 要更新的微博信息。 
     * @return array 
     */ 
    function repost( $text , $repost_status_id) 
    { 
        $param = array(); 
        $param['status'] = $text;
		$param['repost_status_id'] =  $repost_status_id;
        return $this->oauth->post( 'http://api.fanfou.com/statuses/update.json' , $param ); 
    }
    /** 
     * 删除微博 
     * @param mixed $sid 要删除的微博ID 
     */ 
    function destroy( $sid ) 
    { 
        return $this->oauth->post( 'http://api.fanfou.com/statuses/destroy/' . $sid . '.json' ); 
    }
	


//////////////////////////////////////用户相关////////////////////////////////////////////



    /** 
     * 个人资料 
     * @param mixed $uid用户UI 
     */ 
    function show_user( $uid = false ) 
    { 
		$p = array();
		if($uid){
			$p['id'] = $uid;
		}
        return $this->oauth->get( 'http://api.fanfou.com/users/show.json', $p); 
    }
	/** 
     * 通过ID得到用户的好友列表 
     */ 
    function friends( $uid = false ) 
    {
		$p = array();
		if($uid){
			$p['id'] = $uid;
		}
        return $this->oauth-> get( 'http://api.fanfou.com/friends/ids.json' ,  $p ); 
    } 

    /** 
     * 通过ID得到用户的粉丝列表 
     */ 
    function followers( $uid = false ) 
    { 
		$p = array();
		if($uid){
			$p['id'] = $uid;
		}
        return $this->request_with_uid( 'http://api.fanfou.com/followers/ids.json' ,  $p ); 
    } 

	/** 
     * 查看关注请求 
     */ 
    function requests( $page = 1 , $count = 20 ) 
    {
		$p = array(
			'page' => $page,
			'count' => $count,
			'mode' => 'lite'
			
		);
        return $this->oauth-> get( 'http://api.fanfou.com/friendships/requests.json' ,  $p ); 
    } 

    /** 
     * 接受关注请求
     */ 
    function accept( $did ) 
    { 
	$param = array(); 
        $param['id'] = $did; 
	$param['mode'] = 'lite'; 
        return $this->oauth->post( 'http://api.fanfou.com/friendships/accept.json', $param ); 
    } 

    /** 
     * 关注一个用户 
     */ 
    function follow( $uid ) 
    { 
	$param = array(); 
        $param['id'] = $uid; 
	$param['mode'] = 'lite'; 
        return $this->oauth->post( 'http://api.fanfou.com/friendships/create.json', $param ); 
    } 

    /** 
     * 取消关注某用户 
     */ 
    function unfollow( $uid ) 
    { 
        return $this->oauth->post( 'http://api.fanfou.com/friendships/destroy/'.$uid.'.json'); 
    } 

    /** 
     * 判断好友关系是否存在  
     */ 
    function is_followed( $uid_a, $uid_b ) 
    { 
        $param = array(
		'user_a' => $uid_a,
		'user_b' => $uid_b
	);
        return $this->oauth->get( 'http://api.fanfou.com/friendships/exists.json' , $param ); 
    } 

    /** 
     * 获取私信列表 
     * @param int $page 页码 
     * @param int $count 每次返回的最大记录数，最多返回200条，默认20。 
     * @return array 
     */ 
    function list_dm( $page = 1 , $count = 60 , $since_id = false ) 
    { 
	$param = array(); 
        $param['page'] = $page;
	$param['count'] = $count;
	$param['since_id'] = $since_id;
	$param['mode'] = 'lite';
        return $this->request_with_pager( 'http://api.fanfou.com/direct_messages/inbox.json' , $param ); 
    } 

    /** 
     * 发送的私信列表 
     * @param int $page 页码 
     * @param int $count 每次返回的最大记录数，最多返回200条，默认20。 
     * @return array 
     */ 
    function list_dm_sent( $page = 1 , $count = 20 ) 
    { 
        return $this->request_with_pager( 'http://api.fanfou.com/direct_messages/sent.json' , $page , $count ); 
    } 

    /** 
     * 发送私信 
     * @param mixed $uid_or_name UID或微博昵称 
     * @param mixed $text 要发生的消息内容，文本大小必须小于300个汉字。 
     * @return array 
     */ 
    function send_dm( $uid , $text , $in_reply_to_id = false ) 
    { 
        $param = array(); 
        $param['user'] = $uid; 
        $param['text'] = $text; 
        $param['mode'] = 'lite'; 
        if ( $in_reply_to_id )
		$param['in_reply_to_id'] = $in_reply_to_id; 
        return $this->oauth->post( 'http://api.fanfou.com/direct_messages/new.json' , $param  ); 
    } 

    /** 
     * 删除一条私信
     * @param mixed $did 要删除的私信主键ID 
     * @return array 
     */ 
    function delete_dm( $did ) 
    { 
	$param = array(); 
        $param['id'] = $did; 
        return $this->oauth->post( 'http://api.fanfou.com/direct_messages/destroy.json', $param ); 
    } 


    /** 
     * 返回用户的发布的最近20条收藏信息，和用户收藏页面返回内容是一致的。  
     * @param bool $page 返回结果的页序号。 
     * @return array 
     */ 
    function get_favorites( $page = 1 , $count = 20 ) 
    { 
        $param = array(); 
        if( $page ) $param['page'] = $page; 
	if( $count ) $param['count'] = $count;
		
        return $this->oauth->get( ' http://api.fanfou.com/favorites.json' , $param ); 
    } 

    /** 
     * 收藏一条微博信息 
     * @param mixed $sid 收藏的微博id 
     * @return array 
     */ 
    function add_to_favorites( $sid ) 
    { 
        $param = array(); 
        $param['id'] = $sid; 

        return $this->oauth->post( 'http://api.fanfou.com/favorites/create/'. $sid .'.json' ); 
    } 

    /** 
     * 删除微博收藏。 
     * @param mixed $sid 要删除的收藏微博信息ID. 
     * @return array 
     */ 
    function remove_from_favorites( $sid ) 
    { 
        return $this->oauth->post( 'http://api.fanfou.com/favorites/destroy/' . $sid . '.json'  ); 
    } 
    
    
    function verify_credentials() 
    { 
        return $this->oauth->get( 'http://api.fanfou.com/account/verify_credentials.json' );
    }
    



    // ========================================= 

    /** 
     * @ignore 
     */ 
    protected function request_with_pager( $url , $page = false , $count = 60 , $since_id = false , $mode = 'default' ) 
    { 
        $param = array(); 
        if( $page ) $param['page'] = $page; 
        if( $count ) $param['count'] = $count; 
		if( $since_id ) $param['since_id'] = $since_id; 
		if( $mode ) $param['mode'] = $mode; 
		
        return $this->oauth->get($url , $param ); 
    } 

    /** 
     * @ignore 
     */ 
    protected function request_with_uid( $url , $uid_or_name , $page = false , $count = false , $cursor = false , $post = false ) 
    { 
        $param = array(); 
        if( $page ) $param['page'] = $page; 
        if( $count ) $param['count'] = $count; 
        if( $cursor )$param['cursor'] =  $cursor; 

        if( $post ) $method = 'post'; 
        else $method = 'get'; 

        if( is_numeric( $uid_or_name ) ) 
        { 
            $param['user_id'] = $uid_or_name; 
            return $this->oauth->$method($url , $param ); 

        }elseif( $uid_or_name !== null ) 
        { 
            $param['screen_name'] = $uid_or_name; 
            return $this->oauth->$method($url , $param ); 
        } 
        else 
        { 
            return $this->oauth->$method($url , $param ); 
        } 

    } 

} 



?>