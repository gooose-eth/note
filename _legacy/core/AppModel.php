<?php
namespace Core;
use Exception;
use redgoose\Paginate;

/**
 * App model
 */
class AppModel {

  private mixed $connect;

  /**
   * construct
   * @throws Exception
   */
  public function __construct()
  {
    try
    {
      // check path
      if (!($_ENV['APP_PATH_GOOSE'] && $_ENV['APP_TOKEN_PUBLIC'])) throw new Exception();
      if (!file_exists(__PATH__.'/'.$_ENV['APP_PATH_GOOSE'].'connect.php'))
      {
        throw new Exception('Not found connect file');
      }
      // require goose connect
      $this->connect = require __PATH__.'/'.$_ENV['APP_PATH_GOOSE'].'connect.php';
      // initial goose connect
      $this->connect->init((object)[
        'token' => $_ENV['APP_TOKEN_PUBLIC'],
      ]);
      // connect database
      $this->connect->model->connect();
    }
    catch(Exception $e)
    {
      throw new Exception('Failed create unit.');
    }
  }

  /**
   * index
   * @return object
   * @throws Exception
   */
  public function index(): object
  {
    try
    {
      // set variables
      $result = (object)[];
      $result->page = AppUtil::getPage();
      $result->size = (int)$_ENV['APP_DEFAULT_INDEX_SIZE'];

      // set where
      $where = ($app = $_ENV['APP_DEFAULT_APP_SRL']) ? " and app_srl={$app}" : '';
      $where .= " and type LIKE 'public'";
      if (isset($_GET['q']) && $q = $_GET['q']) $where .= ' and (title LIKE \'%'.$q.'%\' or content LIKE \'%'.$q.'%\')';

      // get articles count
      $result->total = $this->connect->model->getCount((object)[
        'table' => 'articles',
        'where' => $where,
        'debug' => __APP_DEBUG__,
      ])->data;

      // get articles
      $result->articles = $this->connect->model->getItems((object)[
        'table' => 'articles',
        'field' => 'srl,category_srl,nest_srl,json,title,regdate,order',
        'order' => '`order` desc, `srl` desc',
        'size' => $_ENV['APP_DEFAULT_INDEX_SIZE'],
        'limit' => [ ($result->page - 1) * $result->size, $result->size ],
        'page' => $result->page,
        'where' => $where,
        'json_field' => ['json'],
        'debug' => __APP_DEBUG__,
      ]);
      $result->articles = (count($result->articles->data ?? []) > 0) ? $result->articles->data : [];
      if (count($result->articles) > 0)
      {
        $result->articles = $this->extendNestNameInItems($result->articles);
        $result->articles = $this->extendCategoryNameInItems($result->articles);
        $result->articles = $this->convertArticles($result->articles);
      }

      // make pagination
      $params = [];
      if (isset($_GET['q'])) $params['q'] = $_GET['q'];
      $result->paginate = ($result->total > 0) ? $this->makePagination($result->total, $result->page, $result->size, $params) : null;

      return $result;
    }
    catch(Exception $e)
    {
      throw new Exception($e->getMessage(), $e->getCode());
    }
  }

  /**
   * index/nest
   * @param object $options
   * @return object
   * @throws Exception
   */
  public function indexNest(object $options): object
  {
    try
    {
      // set variables
      $result = (object)[];
      $result->page = AppUtil::getPage();
      $result->size = (int)$_ENV['APP_DEFAULT_INDEX_SIZE'];
      $nest_id = $options->nest_id;
      $category_srl = $options->category_srl;

      // get nest
      $nest = $this->connect->model->getItem((object)[
        'table' => 'nests',
        'field' => 'srl,name,json',
        'where' => 'id LIKE \''.$nest_id.'\'',
        'json_field' => ['json'],
        'debug' => __APP_DEBUG__,
      ]);
      if ($nest->data)
      {
        $nest = $nest->data;
      }
      else
      {
        throw new Exception('There is no `nest` data.', 404);
      }

      // get categories
      $result->categories = [];
      if ((int)$nest->json->useCategory === 1)
      {
        $result->categories = $this->connect->model->getItems((object)[
          'table' => 'categories',
          'field' => 'srl,name',
          'where' => 'nest_srl='.(int)$nest->srl,
          'order' => 'turn',
          'sort' => 'asc',
        ]);
        if (isset($result->categories->data) && count($result->categories->data) > 0)
        {
          $result->categories = $result->categories->data;
          // extend articles count
          foreach ($result->categories as $k=>$v)
          {
            $cnt = $this->connect->model->getCount((object)[
              'table' => 'articles',
              'where' => "type LIKE 'public' and category_srl={$v->srl}",
            ]);
            $result->categories[$k]->count_article = $cnt->data;
          }
          // extend all item
          $cnt = $this->connect->model->getCount((object)[
            'table' => 'articles',
            'where' => "type LIKE 'public' and nest_srl={$nest->srl}",
          ]);
          array_unshift($result->categories, (object)[
            'srl' => '',
            'name' => 'All',
            'count_article' => $cnt->data,
          ]);
        }
        else
        {
          $result->categories = [];
        }
      }

      // get category
      $result->category = null;
      if ($category_srl)
      {
        $result->category = $this->connect->model->getItem((object)[
          'table' => 'categories',
          'field' => 'srl,name',
          'where' => 'srl='.$category_srl,
        ]);
        if (isset($result->category->data) && $result->category->data->srl)
        {
          $result->category = $result->category->data;
        }
      }

      // set articles params
      $where = ($app = $_ENV['APP_DEFAULT_APP_SRL']) ? " and app_srl={$app}" : '';
      $where .= " and nest_srl={$nest->srl}";
      $where .= " and type LIKE 'public'";
      if ($category_srl) $where .= ' and category_srl='.$category_srl;

      // get articles count
      $result->total = $this->connect->model->getCount((object)[
        'table' => 'articles',
        'where' => $where,
        'debug' => __APP_DEBUG__,
      ])->data;

      // get articles
      $result->articles = $this->connect->model->getItems((object)[
        'table' => 'articles',
        'field' => 'srl,title,json,`order`',
        'where' => $where,
        'order' => '`order` desc, `srl` desc',
        'limit' => [ ($result->page - 1) * $result->size, $result->size ],
        'json_field' => ['json'],
        'debug' => __APP_DEBUG__,
      ]);
      $result->articles = (isset($result->articles->data) && count($result->articles->data)) ? $result->articles->data : [];
      // extend articles
      if ($result->articles && count($result->articles) > 0)
      {
        $result->articles = $this->convertArticles($result->articles);
      }

      // set result
      $result->nest = (object)[
        'srl' => $nest->srl,
        'name' => $nest->name,
      ];

      // make pagination
      $params = [];
      if (isset($_GET['q'])) $params['q'] = $_GET['q'];
      $result->paginate = ($result->total > 0) ? $this->makePagination($result->total, $result->page, $result->size, $params) : null;

      return $result;
    }
    catch(Exception $e)
    {
      throw new Exception($e->getMessage(), $e->getCode());
    }
  }

  /**
   * article
   * @param int $article_srl
   * @return object
   * @throws Exception
   */
  public function article(int $article_srl): object
  {
    try
    {
      if (!(isset($article_srl) && $article_srl))
      {
        throw new Exception('Not found article srl.');
      }

      // set variables
      $result = (object)[];

      // set article params
      $where = ($app = $_ENV['APP_DEFAULT_APP_SRL']) ? " and app_srl={$app}" : '';
      $where .= " and type LIKE 'public'";
      $where .= " and srl={$article_srl}";

      // get article
      $result->article = $this->connect->model->getItem((object)[
        'table' => 'articles',
        'json_field' => ['json'],
        'where' => $where,
        'debug' => __APP_DEBUG__,
      ]);
      if (!isset($result->article->data->srl))
      {
        throw new Exception('no data.');
      }
      $result->article = $result->article->data;

      // update hit
      if (!AppUtil::checkCookie('redgoose-hit-'.$article_srl))
      {
        if (isset($result->article->hit))
        {
          $result->article->hit = (int)$result->article->hit + 1;
          $this->connect->model->edit((object)[
            'table' => 'articles',
            'where' => 'srl='.$article_srl,
            'data' => [ "hit='{$result->article->hit}'" ],
          ]);
        }
        // add key in cookie
        AppUtil::setCookie('redgoose-hit-'.$article_srl, '1', 7);
      }

      // parse markdown
      $parsedown = new ParsedownExtension\Article();
      $result->article->content = $parsedown->text($result->article->content);

      // extend article
      if (isset($result->article->category_srl) && $result->article->category_srl)
      {
        $category = $this->connect->model->getItem((object)[
          'table' => 'categories',
          'field' => 'name',
          'where' => 'srl='.(int)$result->article->category_srl,
          'debug' => __APP_DEBUG__,
        ]);
        if (isset($category->data->name))
        {
          $result->article->category_name = $category->data->name;
        }
      }
      if (isset($result->article->nest_srl) && $result->article->nest_srl)
      {
        $nest = $this->connect->model->getItem((object)[
          'table' => 'nests',
          'where' => 'srl='.(int)$result->article->nest_srl,
        ]);
        if (isset($nest->data->name))
        {
          $result->article->nest_name = $nest->data->name;
        }
      }

      // get comments
      try
      {
        $result->comments = $this->connect->model->getItems((object)[
          'table' => 'comments',
          'where' => 'article_srl='.(int)$result->article->srl,
        ]);
        if (!(isset($result->comments->data) && count($result->comments->data)))
        {
          throw new Exception('no item');
        }
        $parsedown = new ParsedownExtension\Comment();
        $result->comments = $result->comments->data;
        foreach ($result->comments as $key=>$item)
        {
          $result->comments[$key]->content = $parsedown->text($item->content);
          $result->comments[$key]->regdate = explode(' ', $item->regdate)[0];
        }
      }
      catch(Exception $e)
      {
        //
        $result->comments = null;
      }

      return $result;
    }
    catch(Exception $e)
    {
      throw new Exception($e->getMessage(), $e->getCode());
    }
  }

  /**
   * update like
   * @param int $article_srl
   * @return object
   */
  public function like(int $article_srl): object
  {
    $result = (object)[];
    try
    {
      // request
      $res = $this->connect->request('post', "/articles/{$article_srl}/update/", (object)[
        'post' => (object)[
          'type' => 'star',
        ],
      ]);
      if (!($res->success && isset($res->data))) throw new Exception();
      $result->success = true;
      $result->star = $res->data->star;
      AppUtil::setCookie('redgoose-like-'.$article_srl, '1', 30);
    }
    catch(Exception $e)
    {
      $result->success = false;
    }
    return $result;
  }

  /**
   * extend nest name in items
   * @param array $items
   * @return array
   */
  private function extendNestNameInItems(array $items): array
  {
    if (!(isset($items) && count($items))) return [];
    foreach ($items as $k=>$v)
    {
      if (!$v->nest_srl)
      {
        $items[$k]->nest_name = null;
        continue;
      }
      $nest = $this->connect->model->getItem((object)[
        'table' => 'nests',
        'field' => 'name',
        'where' => 'srl='.(int)$v->nest_srl,
      ]);
      $items[$k]->nest_name = $nest->data->name ?? null;
    }
    return $items;
  }

  /**
   * extend category name in items
   * @param array $items
   * @return array
   */
  private function extendCategoryNameInItems(array $items): array
  {
    if (!(isset($items) && count($items))) return [];
    foreach ($items as $k=>$v)
    {
      if (!$v->category_srl)
      {
        $items[$k]->category_name = null;
        continue;
      }
      $category = $this->connect->model->getItem((object)[
        'table' => 'categories',
        'field' => 'name',
        'where' => 'srl='.(int)$v->category_srl,
      ]);
      $items[$k]->category_name = $category->data->name ?? null;
    }
    return $items;
  }

  /**
   * convert articles
   * @param array $items
   * @return array
   */
  private function convertArticles(array $items): array
  {
    if (!(isset($items) && count($items))) return [];
    $result = [];
    foreach ($items as $key=>$item)
    {
      $result[] = (object)[
        'srl' => (int)$item->srl,
        'title' => ($item->title === '.') ? 'untitled article' : $item->title,
        'image' => isset($item->json->thumbnail->path) ? $_ENV['APP_PATH_API_URL'].'/'.$item->json->thumbnail->path : null,
        'nestName' => $item->nest_name ?? null,
        'categoryName' => $item->category_name ?? null,
        'regdate' => $item->order,
      ];
    }
    return $result;
  }

  /**
   * make pagination
   * 모바일과 데스크탑 네비게이션 객체를 만들어준다.
   * @param int $total
   * @param int $page
   * @param int $size
   * @param array $params
   * @return object
   */
  private function makePagination(int $total, int $page=1, int $size=10, array $params = []): object
  {
    $result = (object)[
      'total' => $total,
      'page' => $page,
    ];
    $paginateUnit = new Paginate((object)[
      'total' => $total,
      'page' => $page,
      'size' => $size,
      'params' => $params,
      'scale' => 3,
    ]);
    $result->mobile = $paginateUnit->createElements([ 'paginate', 'paginate--mobile' ], './');
    $paginateUnit->update((object)[ 'scale' => 10 ]);
    $result->desktop = $paginateUnit->createElements([ 'paginate', 'paginate--desktop' ], './');
    return $result;
  }

}