<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Sampledata.Testing
 *
 * @copyright   (C) 2017 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Categories\Administrator\Model\CategoryModel;
use Joomla\Database\DatabaseDriver;

/**
 * Sampledata - Testing Plugin
 *
 * @since  3.8.0
 */
class PlgSampledataTesting extends CMSPlugin
{
	/**
	 * Database object
	 *
	 * @var    DatabaseDriver
	 *
	 * @since  3.8.0
	 */
	protected $db;

	/**
	 * Application object
	 *
	 * @var    AdministratorApplication
	 *
	 * @since  3.8.0
	 */
	protected $app;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 *
	 * @since  3.8.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Holds the category model
	 *
	 * @var    CategoryModel
	 *
	 * @since  3.8.0
	 */
	private $categoryModel;

	/**
	 * Holds the menuitem model
	 *
	 * @var    \Joomla\Component\Menus\Administrator\Model\ItemModel
	 *
	 * @since  3.8.0
	 */
	private $menuItemModel;

	/**
	 * Get an overview of the proposed sampledata.
	 *
	 * @return  object  Object containing the name, title, description, icon and steps.
	 *
	 * @since  3.8.0
	 */
	public function onSampledataGetOverview()
	{
		$data              = new stdClass;
		$data->name        = $this->_name;
		$data->title       = Text::_('PLG_SAMPLEDATA_TESTING_OVERVIEW_TITLE');
		$data->description = Text::_('PLG_SAMPLEDATA_TESTING_OVERVIEW_DESC');
		$data->icon        = 'bolt';
		$data->steps       = 9;

		return $data;
	}

	/**
	 * First step to enter the sampledata. Tags
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep1()
	{
		if ($this->app->input->get('type') !== $this->_name)
		{
			return;
		}

		if (!ComponentHelper::isEnabled('com_tags'))
		{
			$response            = array();
			$response['success'] = true;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_SKIPPED', 1, 'com_tags');

			return $response;
		}

		/** @var \Joomla\Component\Tags\Administrator\Model\TagModel $model */
		$model = $this->app->bootComponent('com_tags')->getMVCFactory()->createModel('Tag', 'Administrator', ['ignore_request' => true]);
		$access = (int) $this->app->get('access', 1);
		$user   = Factory::getUser();
		$tagIds = array();

		// Create first three tags.
		for ($i = 0; $i <= 2; $i++)
		{
			$title = Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_TAG_' . $i . '_TITLE');
			$tag   = array(
				'id'              => 0,
				'title'           => $title,
				'alias'           => ApplicationHelper::stringURLSafe($title),
				'parent_id'       => 1,
				'published'       => 1,
				'access'          => $access,
				'created_user_id' => $user->id,
				'language'        => '*',
				'description'     => '',
			);

			try
			{
				if (!$model->save($tag))
				{
					Factory::getLanguage()->load('com_tags');
					throw new Exception(Text::_($model->getError()));
				}
			}
			catch (Exception $e)
			{
				$response            = array();
				$response['success'] = false;
				$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 1, $e->getMessage());

				return $response;
			}

			$tagIds[] = $model->getState('tag.id');
		}

		// Create fourth tag as child of the third.
		$title = Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_TAG_3_TITLE');
		$tag   = array(
			'id'              => 0,
			'title'           => $title,
			'alias'           => ApplicationHelper::stringURLSafe($title),
			'parent_id'       => $tagIds[2],
			'published'       => 1,
			'access'          => $access,
			'created_user_id' => $user->id,
			'language'        => '*',
			'description'     => '',
		);

		try
		{
			if (!$model->save($tag))
			{
				Factory::getLanguage()->load('com_tags');
				throw new Exception(Text::_($model->getError()));
			}
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 1, $e->getMessage());

			return $response;
		}

		$tagIds[] = $model->getState('tag.id');

		// Storing IDs in UserState for later usage.
		$this->app->setUserState('sampledata.testing.tags', $tagIds);

		$response            = array();
		$response['success'] = true;
		$response['message'] = Text::_('PLG_SAMPLEDATA_TESTING_STEP1_SUCCESS');

		return $response;
	}

	/**
	 * Second step to enter the sampledata. Banners
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep2()
	{
		if ($this->app->input->get('type') !== $this->_name)
		{
			return;
		}

		if (!ComponentHelper::isEnabled('com_banners'))
		{
			$response            = array();
			$response['success'] = true;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_SKIPPED', 2, 'com_banners');

			return $response;
		}

		$factory = $this->app->bootComponent('com_banners')->getMVCFactory();

		/** @var Joomla\Component\Banners\Administrator\Model\ClientModel $clientModel */
		$clientModel = $factory->createModel('Client', 'Administrator', ['ignore_request' => true]);

		/** @var Joomla\Component\Banners\Administrator\Model\BannerModel $bannerModel */
		$bannerModel = $factory->createModel('Banner', 'Administrator', ['ignore_request' => true]);

		$user = Factory::getUser();

		// Add categories.
		$categories   = array();
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_CATEGORY_0_TITLE'),
			'parent_id' => 1,
		);

		try
		{
			$catIds = $this->addCategories($categories, 'com_banners', 1);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 2, $e->getMessage());

			return $response;
		}

		$this->app->setUserState('sampledata.testing.banners.catids', $catIds);

		// Add Clients.
		$clients     = array();
		$clients[]   = array(
			'name'              => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_CLIENT_1_NAME'),
			'contact'           => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_CLIENT_1_CONTACT'),
			'purchase_type'     => -1,
			'track_clicks'      => -1,
			'track_impressions' => -1,
		);
		$clients[]   = array(
			'name'              => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_CLIENT_2_NAME'),
			'contact'           => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_CLIENT_2_CONTACT'),
			'email'             => 'banner@example.com',
			'purchase_type'     => -1,
			'track_clicks'      => 0,
			'track_impressions' => 0,
		);
		$clients[]   = array(
			'name'              => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_CLIENT_3_NAME'),
			'contact'           => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_CLIENT_3_CONTACT'),
			'purchase_type'     => -1,
			'track_clicks'      => 0,
			'track_impressions' => 0,
		);
		$clientIds   = array();

		foreach ($clients as $client)
		{
			// Set values which are always the same.
			$client['id']        = 0;
			$client['email']     = 'banner@example.com';
			$client['state']     = 1;
			$client['metakey']   = '';
			$client['extrainfo'] = '';

			try
			{
				if (!$clientModel->save($client))
				{
					Factory::getLanguage()->load('com_banners');
					throw new Exception(Text::_($clientModel->getError()));
				}
			}
			catch (Exception $e)
			{
				$response            = array();
				$response['success'] = false;
				$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 2, $e->getMessage());

				return $response;
			}

			$clientIds[] = $clientModel->getState('banner.id');
		}

		// Add Banners.
		$banners   = array();
		$banners[] = array(
			'cid'         => $clientIds[2],
			'name'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_BANNER_1_NAME'),
			'clickurl'    => 'https://community.joomla.org/the-joomla-shop.html',
			'catid'       => $catIds[0],
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_BANNER_1_DESC'),
			'ordering'    => 1,
			'params'      => '{"imageurl":"images/banners/white.png","width":"","height":"","alt":"Joomla! Books"}',
		);
		$banners[] = array(
			'cid'         => $clientIds[1],
			'name'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_BANNER_2_NAME'),
			'clickurl'    => 'https://shop.joomla.org',
			'catid'       => $catIds[0],
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_BANNER_2_DESC'),
			'ordering'    => 2,
			'params'      => '{"imageurl":"images/banners/white.png","width":"","height":"","alt":"Joomla! Shop"}',
		);
		$banners[] = array(
			'cid'         => $clientIds[0],
			'name'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_BANNER_3_NAME'),
			'clickurl'    => 'https://www.joomla.org/sponsor.html',
			'catid'       => $catIds[0],
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_BANNERS_BANNER_3_DESC'),
			'ordering'    => 3,
			'params'      => '{"imageurl":"images/banners/white.png","width":"","height":"","alt":""}',
		);

		foreach ($banners as $banner)
		{
			// Set values which are always the same.
			$banner['id']               = 0;
			$banner['type']             = 0;
			$banner['state']            = 1;
			$banner['alias']            = ApplicationHelper::stringURLSafe($banner['name']);
			$banner['custombannercode'] = '';
			$banner['metakey']          = '';
			$banner['purchase_type']    = -1;
			$banner['created_by']       = $user->id;
			$banner['created_by_alias'] = 'Joomla';
			$banner['language']         = 'en-GB';

			try
			{
				if (!$bannerModel->save($banner))
				{
					Factory::getLanguage()->load('com_banners');
					throw new Exception(Text::_($bannerModel->getError()));
				}
			}
			catch (Exception $e)
			{
				$response            = array();
				$response['success'] = false;
				$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 2, $e->getMessage());

				return $response;
			}
		}

		$response            = array();
		$response['success'] = true;
		$response['message'] = Text::_('PLG_SAMPLEDATA_TESTING_STEP2_SUCCESS');

		return $response;
	}

	/**
	 * Third step to enter the sampledata. Content 1/2
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep3()
	{
		if ($this->app->input->get('type') !== $this->_name)
		{
			return;
		}

		if (!ComponentHelper::isEnabled('com_content'))
		{
			$response            = array();
			$response['success'] = true;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_SKIPPED', 3, 'com_content');

			return $response;
		}

		// Insert first level of categories.
		$categories   = array();
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_TITLE'),
			'parent_id' => 1,
		);

		try
		{
			$catIdsLevel1 = $this->addCategories($categories, 'com_content', 1);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 3, $e->getMessage());

			return $response;
		}

		// Insert second level of categories.
		$categories   = array();
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_TITLE'),
			'parent_id' => $catIdsLevel1[0],
		);
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_1_TITLE'),
			'parent_id' => $catIdsLevel1[0],
			'language'  => 'en-GB',
		);
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_2_TITLE'),
			'parent_id' => $catIdsLevel1[0],
		);

		try
		{
			$catIdsLevel2 = $this->addCategories($categories, 'com_content', 2);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 3, $e->getMessage());

			return $response;
		}

		// Insert third level of categories.
		$categories   = array();
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_DESC'),
			'parent_id'   => $catIdsLevel2[0],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_1_1_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_1_1_DESC'),
			'parent_id'   => $catIdsLevel2[1],
			'params'      => '{"category_layout":"","image":"images/sampledata/parks/banner_cradle.jpg"}',
			'language'    => 'en-GB',
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_1_2_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_1_2_DESC'),
			'parent_id'   => $catIdsLevel2[1],
			'language'    => 'en-GB',
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_2_3_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_2_3_DESC'),
			'parent_id'   => $catIdsLevel2[2],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_2_4_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_2_4_DESC'),
			'parent_id'   => $catIdsLevel2[2],
		);

		try
		{
			$catIdsLevel3 = $this->addCategories($categories, 'com_content', 3);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 3, $e->getMessage());

			return $response;
		}

		// Insert fourth level of categories.
		$categories   = array();
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_0_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_0_DESC'),
			'parent_id'   => $catIdsLevel3[0],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_DESC'),
			'parent_id'   => $catIdsLevel3[0],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_2_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_2_DESC'),
			'parent_id'   => $catIdsLevel3[0],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_3_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_3_DESC'),
			'parent_id'   => $catIdsLevel3[0],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_4_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_4_DESC'),
			'parent_id'   => $catIdsLevel3[0],
		);
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_1_2_5_TITLE'),
			'parent_id' => $catIdsLevel3[2],
			'language'  => 'en-GB',
		);
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_1_2_6_TITLE'),
			'parent_id' => $catIdsLevel3[2],
			'language'  => 'en-GB',
		);

		try
		{
			$catIdsLevel4 = $this->addCategories($categories, 'com_content', 4);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 3, $e->getMessage());

			return $response;
		}

		// Insert fifth level of categories.
		$categories   = array();
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_0_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_0_DESC'),
			'parent_id'   => $catIdsLevel4[1],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_1_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_1_DESC'),
			'parent_id'   => $catIdsLevel4[1],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_2_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_2_DESC'),
			'parent_id'   => $catIdsLevel4[1],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_3_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_3_DESC'),
			'parent_id'   => $catIdsLevel4[1],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_4_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_1_4_DESC'),
			'parent_id'   => $catIdsLevel4[1],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_2_5_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_2_5_DESC'),
			'parent_id'   => $catIdsLevel4[2],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_2_6_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_2_6_DESC'),
			'parent_id'   => $catIdsLevel4[2],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_2_7_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_CATEGORY_0_0_0_2_7_DESC'),
			'parent_id'   => $catIdsLevel4[2],
		);

		try
		{
			$catIdsLevel5 = $this->addCategories($categories, 'com_content', 5);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 3, $e->getMessage());

			return $response;
		}

		$this->app->setUserState('sampledata.testing.articles.catids1', $catIdsLevel1);
		$this->app->setUserState('sampledata.testing.articles.catids2', $catIdsLevel2);
		$this->app->setUserState('sampledata.testing.articles.catids3', $catIdsLevel3);
		$this->app->setUserState('sampledata.testing.articles.catids4', $catIdsLevel4);
		$this->app->setUserState('sampledata.testing.articles.catids5', $catIdsLevel5);

		$response            = array();
		$response['success'] = true;
		$response['message'] = Text::_('PLG_SAMPLEDATA_TESTING_STEP3_SUCCESS');

		return $response;
	}

	/**
	 * Fourth step to enter the sampledata. Content 2/2
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  4.0.0
	 */
	public function onAjaxSampledataApplyStep4()
	{
		if ($this->app->input->get('type') !== $this->_name)
		{
			return;
		}

		if (!ComponentHelper::isEnabled('com_content'))
		{
			$response            = array();
			$response['success'] = true;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_SKIPPED', 4, 'com_content');

			return $response;
		}

		ComponentHelper::getParams('com_content')->set('workflow_enabled', 0);

		$catIdsLevel1 = $this->app->getUserState('sampledata.testing.articles.catids1');
		$catIdsLevel2 = $this->app->getUserState('sampledata.testing.articles.catids2');
		$catIdsLevel3 = $this->app->getUserState('sampledata.testing.articles.catids3');
		$catIdsLevel4 = $this->app->getUserState('sampledata.testing.articles.catids4');
		$catIdsLevel5 = $this->app->getUserState('sampledata.testing.articles.catids5');
		$tagIds = $this->app->getUserState('sampledata.testing.tags', array());

		$articles = array(
			// Articles 0 - 9
			array(
				'catid'    => $catIdsLevel4[0],
				'ordering' => 7,
			),
			array(
				'catid'    => $catIdsLevel5[0],
				'ordering' => 5,
			),
			array(
				'catid'    => $catIdsLevel5[0],
				'ordering' => 6,
			),
			array(
				'catid'    => $catIdsLevel5[0],
				'ordering' => 7,
			),
			array(
				'catid'    => $catIdsLevel4[4],
				'ordering' => 3,
			),
			array(
				'catid'    => $catIdsLevel2[1],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[2],
				'ordering' => 6,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 4,
				'featured' => 1,
			),
			array(
				'catid'    => $catIdsLevel4[0],
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel4[0],
				'ordering' => 1,
			),
			// Articles 10 - 19
			array(
				'catid'    => $catIdsLevel4[6],
				'images'   => array(
					'image_intro'            => 'images/sampledata/parks/landscape/250px_cradle_mountain_seen_from_barn_bluff.jpg',
					'image_intro_alt'        => 'Cradle Mountain',
					'image_fulltext'         => 'images/sampledata/parks/landscape/250px_cradle_mountain_seen_from_barn_bluff.jpg',
					'image_fulltext_alt'     => 'Cradle Mountain',
					'image_fulltext_caption' => 'Source: https://commons.wikimedia.org/wiki/File:Rainforest,bluemountainsNSW.jpg'
						. ' Author: Alan J.W.C. License: GNU Free Documentation License v. 1.2 or later',
				),
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[2],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel2[2],
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel4[4],
				'ordering' => 5,
			),
			array(
				'catid'    => $catIdsLevel4[4],
				'ordering' => 6,
			),
			array(
				'catid'    => $catIdsLevel5[2],
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel3[1],
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel3[1],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[2],
				'ordering' => 3,
			),
			array(
				'catid'    => $catIdsLevel2[2],
				'ordering' => 1,
			),
			// Articles 20 - 29
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 8,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 9,
			),
			array(
				'catid'    => $catIdsLevel3[3],
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 2,
				'tags'     => array_map('strval', $tagIds),
				'featured' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[4],
				'images'   => array(
					'image_intro'            => 'images/sampledata/parks/animals/180px_koala_ag1.jpg',
					'image_intro_alt'        => 'Koala Thumbnail',
					'image_fulltext'         => 'images/sampledata/parks/animals/800px_koala_ag1.jpg',
					'image_fulltext_alt'     => 'Koala Climbing Tree',
					'image_fulltext_caption' => 'Source: https://en.wikipedia.org/wiki/File:Koala-ag1.jpg'
						. ' Author: Arnaud Gaillard License: Creative Commons Share Alike Attribution Generic 1.0',
				),
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel5[3],
				'ordering' => 3,
			),
			array(
				'catid'    => $catIdsLevel5[0],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[1],
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel5[4],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[0],
				'ordering' => 2,
			),
			// Articles 30 - 39
			array(
				'catid'    => $catIdsLevel5[0],
				'ordering' => 3,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 10,
			),
			array(
				'catid'    => $catIdsLevel5[4],
				'images'   => array(
					'image_intro'            => 'images/sampledata/parks/animals/200px_phyllopteryx_taeniolatus1.jpg',
					'image_intro_alt'        => 'Phyllopteryx',
					'image_fulltext'         => 'images/sampledata/parks/animals/800px_phyllopteryx_taeniolatus1.jpg',
					'image_fulltext_alt'     => 'Phyllopteryx',
					'image_fulltext_caption' => 'Source: https://en.wikipedia.org/wiki/File:Phyllopteryx_taeniolatus1.jpg'
						. ' Author: Richard Ling License: GNU Free Documentation License v 1.2 or later',
				),
				'ordering' => 3,
			),
			array(
				'catid'    => $catIdsLevel4[6],
				'images'   => array(
					'image_intro'            => 'images/sampledata/parks/landscape/120px_pinnacles_western_australia.jpg',
					'image_intro_alt'        => 'Kings Canyon',
					'image_fulltext'         => 'images/sampledata/parks/landscape/800px_pinnacles_western_australia.jpg',
					'image_fulltext_alt'     => 'Kings Canyon',
					'image_fulltext_caption' => 'Source: https://commons.wikimedia.org/wiki/File:Pinnacles_Western_Australia.jpg'
						. ' Author: Martin Gloss License: GNU Free Documentation license v 1.2 or later.',
				),
				'ordering' => 4,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 5,
				'featured' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[2],
				'ordering' => 4,
			),
			array(
				'catid'    => $catIdsLevel5[0],
				'ordering' => 4,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 11,
			),
			array(
				'catid'    => $catIdsLevel4[0],
				'ordering' => 3,
			),
			array(
				'catid'    => $catIdsLevel5[3],
				'ordering' => 4,
			),
			// Articles 40 - 49
			array(
				'catid'    => $catIdsLevel4[4],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel1[0],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[4],
				'images'   => array(
					'image_intro'            => 'images/sampledata/parks/animals/220px_spottedquoll_2005_seanmcclean.jpg',
					'image_intro_alt'        => 'Spotted Quoll',
					'image_fulltext'         => 'images/sampledata/parks/animals/789px_spottedquoll_2005_seanmcclean.jpg',
					'image_fulltext_alt'     => 'Spotted Quoll',
					'image_fulltext_caption' => 'Source: https://en.wikipedia.org/wiki/File:SpottedQuoll_2005_SeanMcClean.jpg'
						. ' Author: Sean McClean License: GNU Free Documentation License v 1.2 or later',
				),
				'ordering' => 4,
			),
			array(
				'catid'    => $catIdsLevel5[3],
				'ordering' => 5,
			),
			array(
				'catid'    => $catIdsLevel5[3],
				'ordering' => 6,
			),
			array(
				'catid'    => $catIdsLevel4[4],
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 3,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel4[2],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 6,
				'featured' => 1,
			),
			// Articles 50 - 59
			array(
				'catid'    => $catIdsLevel4[4],
				'ordering' => 4,
			),
			array(
				'catid'    => $catIdsLevel4[0],
				'ordering' => 5,
			),
			array(
				'catid'    => $catIdsLevel2[0],
				'ordering' => 7,
			),
			array(
				'catid'    => $catIdsLevel5[1],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[4],
				'images'   => array(
					'image_intro'            => 'images/sampledata/parks/animals/180px_wobbegong.jpg',
					'image_intro_alt'        => 'Wobbegon',
					'image_fulltext'         => 'images/sampledata/parks/animals/800px_wobbegong.jpg',
					'image_fulltext_alt'     => 'Wobbegon',
					'image_fulltext_caption' => 'Source: https://en.wikipedia.org/wiki/File:Wobbegong.jpg'
						. ' Author: Richard Ling License: GNU Free Documentation License v 1.2 or later',
				),
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel3[3],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel5[3],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel4[0],
				'ordering' => 4,
			),
			array(
				'catid'    => $catIdsLevel5[4],
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel4[4],
				'ordering' => 7,
			),
			// Articles 60 - 68
			array(
				'catid'    => $catIdsLevel4[6],
				'images'   => array(
					'image_intro'            => 'images/sampledata/parks/landscape/120px_rainforest_bluemountainsnsw.jpg',
					'float_intro'            => 'none',
					'image_intro_alt'        => 'Rain Forest Blue Mountains',
					'image_fulltext'         => 'images/sampledata/parks/landscape/727px_rainforest_bluemountainsnsw.jpg',
					'image_fulltext_alt'     => 'Rain Forest Blue Mountains',
					'image_fulltext_caption' => 'Source: https://commons.wikimedia.org/wiki/File:Rainforest,bluemountainsNSW.jpg'
						. ' Author: Adam J.W.C. License: GNU Free Public Documentation License',
				),
				'ordering' => 2,
			),
			array(
				'catid'    => $catIdsLevel4[6],
				'images'   => array(
					'image_intro'            => 'images/sampledata/parks/landscape/180px_ormiston_pound.jpg',
					'float_intro'            => 'none',
					'image_intro_alt'        => 'Ormiston Pound',
					'image_fulltext'         => 'images/sampledata/parks/landscape/800px_ormiston_pound.jpg',
					'image_fulltext_alt'     => 'Ormiston Pound',
					'image_fulltext_caption' => 'Source: https://commons.wikimedia.org/wiki/File:Ormiston_Pound.JPG'
						. ' Author: License: GNU Free Public Documentation License',
				),
				'ordering' => 3,
			),
			array(
				'catid'    => $catIdsLevel5[1],
				'ordering' => 3,
			),
			array(
				'catid'      => $catIdsLevel2[0],
				'state'  => 2,
				'ordering'   => 0,
			),
			array(
				'catid'    => $catIdsLevel4[4],
				'ordering' => 1,
			),
			array(
				'catid'    => $catIdsLevel4[4],
				'ordering' => 0,
			),
			array(
				'catid'    => $catIdsLevel5[3],
				'ordering' => 0,
			),
			array(
				'catid'    => $catIdsLevel5[0],
				'tags'     => array_map('strval', array_slice($tagIds, 0, 3)),
				'ordering' => 0,
			),
			array(
				'catid'    => $catIdsLevel5[0],
				'ordering' => 0,
			),
		);

		try
		{
			$ids = $this->addArticles($articles);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 4, $e->getMessage());

			return $response;
		}

		$articleNamespace = (array) $this->app->getUserState('sampledata.testing.articles');
		$this->app->setUserState('sampledata.testing.articles', array_merge($ids, $articleNamespace));

		$response            = array();
		$response['success'] = true;
		$response['message'] = Text::_('PLG_SAMPLEDATA_TESTING_STEP4_SUCCESS');

		return $response;
	}

	/**
	 * Fifth step to enter the sampledata. Contacts
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep5()
	{
		if ($this->app->input->get('type') !== $this->_name)
		{
			return;
		}

		if (!ComponentHelper::isEnabled('com_contact'))
		{
			$response            = array();
			$response['success'] = true;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_SKIPPED', 5, 'com_contact');

			return $response;
		}

		$model  = $this->app->bootComponent('com_contact')->getMVCFactory()->createModel('Contact', 'Administrator', ['ignore_request' => true]);
		$access = (int) $this->app->get('access', 1);
		$user   = Factory::getUser();

		// Insert first level of categories.
		$categories   = array();
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CATEGORY_0_TITLE'),
			'parent_id' => 1,
		);

		try
		{
			$catIdsLevel1 = $this->addCategories($categories, 'com_contact', 1);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 5, $e->getMessage());

			return $response;
		}

		// Insert second level of categories.
		$categories   = array();
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CATEGORY_0_0_TITLE'),
			'parent_id' => $catIdsLevel1[0],
		);
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CATEGORY_0_1_TITLE'),
			'parent_id' => $catIdsLevel1[0],
		);

		try
		{
			$catIdsLevel2 = $this->addCategories($categories, 'com_contact', 2);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 5, $e->getMessage());

			return $response;
		}

		// Insert third level of categories.
		$categories   = array();
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CATEGORY_0_1_0_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CATEGORY_0_1_0_DESC'),
			'parent_id'   => $catIdsLevel2[1],
		);
		$categories[] = array(
			'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CATEGORY_0_1_1_TITLE'),
			'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CATEGORY_0_1_1_DESC'),
			'parent_id'   => $catIdsLevel2[1],
		);

		try
		{
			$catIdsLevel3 = $this->addCategories($categories, 'com_contact', 3);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 5, $e->getMessage());

			return $response;
		}

		// Insert fourth level of categories.
		$categories = array();

		// Categories A-Z.
		for ($i = 65; $i <= 90; $i++)
		{
			$categories[] = array(
				'title'     => chr($i),
				'parent_id' => $catIdsLevel3[1],
			);
		}

		try
		{
			$catIdsLevel4 = $this->addCategories($categories, 'com_contact', 4);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 5, $e->getMessage());

			return $response;
		}

		$contacts   = array(
			array(
				'name'         => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_NAME'),
				'con_position' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_POSITION'),
				'address'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_ADDRESS'),
				'suburb'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_SUBURB'),
				'state'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_STATE'),
				'country'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_COUNTRY'),
				'postcode'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_POSTCODE'),
				'telephone'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_TELEPHONE'),
				'fax'          => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_FAX'),
				'misc'         => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_MISC'),
				'sortname1'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_SORTNAME1'),
				'sortname2'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_SORTNAME2'),
				'sortname3'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_0_SORTNAME3'),
				'image'        => 'images/powered_by.png',
				'email_to'     => 'email@example.com',
				'default_con'  => 1,
				'featured'     => 1,
				'catid'        => $catIdsLevel1[0],
				'params'       => array(
					'show_links' => 1,
					'linka_name' => 'Twitter',
					'linka'      => 'https://twitter.com/joomla',
					'linkb_name' => 'YouTube',
					'linkb'      => 'https://www.youtube.com/user/joomla',
					'linkc_name' => 'Facebook',
					'linkc'      => 'https://www.facebook.com/joomla',
					'linkd_name' => 'LinkedIn',
					'linkd'      => 'https://www.linkedin.com/company/joomla',
					'linke_name' => 'Scribed',
					'linke'      => 'https://www.scribd.com/people/view/504592-joomla',
				),
			),
			array(
				'name'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_1_NAME'),
				'email_to' => 'webmaster@example.com',
				'featured' => 1,
				'catid'    => $catIdsLevel2[0],
			),
			array(
				'name'  => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_2_NAME'),
				'misc'  => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_2_MISC'),
				'catid' => $catIdsLevel3[0],
			),
			array(
				'name'  => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_3_NAME'),
				'misc'  => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_3_MISC'),
				'catid' => $catIdsLevel3[0],
			),
			array(
				'name'         => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_4_NAME'),
				'con_position' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_4_POSITION'),
				'address'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_4_ADDRESS'),
				'state'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_4_STATE'),
				'misc'         => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_4_MISC'),
				'image'        => 'images/sampledata/fruitshop/bananas_2.jpg',
				'catid'        => $catIdsLevel4[1],
				'params'       => array(
					'show_contact_category' => 'show_with_link',
					'presentation_style'    => 'plain',
					'show_position'         => 1,
					'show_state'            => 1,
					'show_country'          => 1,
					'show_links'            => 1,
					'linka_name'            => 'Wikipedia: Banana English',
					'linka'                 => 'https://en.wikipedia.org/wiki/Banana',
					'linkb_name'            => 'Wikipedia: हिन्दी केला',
					'linkb'                 => 'https://hi.wikipedia.org/wiki/%E0%A4%95%E0%A5%87%E0%A4%B2%E0%A4%BE',
					'linkc_name'            => 'Wikipedia:Banana Português',
					'linkc'                 => 'https://pt.wikipedia.org/wiki/Banana',
					'linkd_name'            => 'Wikipedia: Банан  Русский',
					'linkd'                 => 'https://ru.wikipedia.org/wiki/%D0%91%D0%B0%D0%BD%D0%B0%D0%BD',
					'linke_name'            => '',
					'linke'                 => '',
					'contact_layout'        => 'beez5:encyclopedia',
				),
			),
			array(
				'name'         => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_5_NAME'),
				'con_position' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_5_POSITION'),
				'address'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_5_ADDRESS'),
				'state'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_5_STATE'),
				'misc'         => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_5_MISC'),
				'image'        => 'images/sampledata/fruitshop/apple.jpg',
				'catid'        => $catIdsLevel4[0],
				'params'       => array(
					'presentation_style' => 'plain',
					'show_links'         => 1,
					'linka_name'         => 'Wikipedia: Apples English',
					'linka'              => 'https://en.wikipedia.org/wiki/Apple',
					'linkb_name'         => 'Wikipedia: Manzana Español',
					'linkb'              => 'https://es.wikipedia.org/wiki/Manzana',
					'linkc_name'         => 'Wikipedia: 苹果 中文',
					'linkc'              => 'https://zh.wikipedia.org/zh/苹果',
					'linkd_name'         => 'Wikipedia: Tofaa Kiswahili',
					'linkd'              => 'https://sw.wikipedia.org/wiki/Tofaa',
					'linke_name'         => '',
					'linke'              => '',
					'contact_layout'     => 'beez5:encyclopedia',
				),
			),
			array(
				'name'         => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_6_NAME'),
				'con_position' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_6_POSITION'),
				'address'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_6_ADDRESS'),
				'state'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_6_STATE'),
				'misc'         => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_6_MISC'),
				'image'        => 'images/sampledata/fruitshop/tamarind.jpg',
				'catid'        => $catIdsLevel4[19],
				'params'       => array(
					'presentation_style' => 'plain',
					'show_links'         => 1,
					'linka_name'         => 'Wikipedia: Tamarind English',
					'linka'              => 'https://en.wikipedia.org/wiki/Tamarind',
					'linkb_name'         => 'Wikipedia: তেঁতুল  বাংলা',
					'linkb'              => 'https://bn.wikipedia.org/wiki/তেঁতুল',
					'linkc_name'         => 'Wikipedia: Tamarinier Français',
					'linkc'              => 'https://fr.wikipedia.org/wiki/Tamarinier',
					'linkd_name'         => 'Wikipedia:Tamaline lea faka-Tonga',
					'linkd'              => 'https://to.wikipedia.org/wiki/Tamaline',
					'linke_name'         => '',
					'linke'              => '',
					'contact_layout'     => 'beez5:encyclopedia',
				),
			),
			array(
				'name'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_7_NAME'),
				'suburb'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_7_SUBURB'),
				'country'   => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_7_COUNTRY'),
				'address'   => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_7_ADDRESS'),
				'telephone' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_7_TELEPHONE'),
				'misc'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTACT_CONTACT_7_MISC'),
				'catid'     => $catIdsLevel2[1],
			),
		);
		$contactIds = array();

		foreach ($contacts as $contact)
		{
			// Set values which are always the same.
			$contact['id']              = 0;
			$contact['access']          = $access;
			$contact['created_user_id'] = $user->id;
			$contact['alias']           = ApplicationHelper::stringURLSafe($contact['name']);
			$contact['published']       = 1;
			$contact['language']        = '*';
			$contact['associations']    = array();

			// Reset some fields if not specified.
			$fields = array('con_position', 'address', 'suburb', 'state', 'country', 'postcode', 'telephone', 'fax',
				'misc', 'sortname1', 'sortname2', 'sortname3', 'email_to', 'image', );

			// Temporary, they are waiting for PR #14112
			$fields[] = 'metakey';
			$fields[] = 'metadesc';
			$contact['metadata'] = '{}';

			foreach ($fields as $field)
			{
				if (!isset($contact[$field]))
				{
					$contact[$field] = '';
				}
			}

			// Set featured state to published if not set.
			if (!isset($contact['featured']))
			{
				$contact['featured'] = 0;
			}

			// Set state to published if not set.
			if (!isset($contact['default_con']))
			{
				$contact['default_con'] = 0;
			}

			// Set params to empty if not set.
			if (!isset($contact['params']))
			{
				$contact['params'] = array(
					'linka_name' => '',
					'linka'      => '',
					'linkb_name' => '',
					'linkb'      => '',
					'linkc_name' => '',
					'linkc'      => '',
					'linkd_name' => '',
					'linkd'      => '',
					'linke_name' => '',
					'linke'      => '',
				);
			}

			try
			{
				if (!$model->save($contact))
				{
					Factory::getLanguage()->load('com_contact');
					throw new Exception(Text::_($model->getError()));
				}
			}
			catch (Exception $e)
			{
				$response            = array();
				$response['success'] = false;
				$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 5, $e->getMessage());

				return $response;
			}

			// Get ID from category we just added
			$contactIds[] = $model->getItem()->id;
		}

		// Storing IDs in UserState for later usage.
		$this->app->setUserState('sampledata.testing.contacts', $contactIds);
		$this->app->setUserState('sampledata.testing.contacts.catids1', $catIdsLevel1);
		$this->app->setUserState('sampledata.testing.contacts.catids2', $catIdsLevel2);
		$this->app->setUserState('sampledata.testing.contacts.catids3', $catIdsLevel3);
		$this->app->setUserState('sampledata.testing.contacts.catids4', $catIdsLevel4);

		$response            = array();
		$response['success'] = true;
		$response['message'] = Text::_('PLG_SAMPLEDATA_TESTING_STEP5_SUCCESS');

		return $response;
	}

	/**
	 * Sixth step to enter the sampledata. Newsfeed.
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep6()
	{
		if ($this->app->input->get('type') !== $this->_name)
		{
			return;
		}

		if (!ComponentHelper::isEnabled('com_newsfeeds'))
		{
			$response            = array();
			$response['success'] = true;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_SKIPPED', 6, 'com_newsfeed');

			return $response;
		}

		/** @var \Joomla\Component\Newsfeeds\Administrator\Model\NewsfeedModel $model */
		$model  = $this->app->bootComponent('com_newsfeeds')->getMVCFactory()->createModel('Newsfeed', 'Administrator', ['ignore_request' => true]);
		$access = (int) $this->app->get('access', 1);
		$user   = Factory::getUser();

		// Insert first level of categories.
		$categories   = array();
		$categories[] = array(
			'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_NEWSFEEDS_CATEGORY_0_TITLE'),
			'parent_id' => 1,
		);

		try
		{
			$catIdsLevel1 = $this->addCategories($categories, 'com_newsfeeds', 1);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 6, $e->getMessage());

			return $response;
		}

		$newsfeeds    = array(
			array(
				'name'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_NEWSFEEDS_NEWSFEED_0_NAME'),
				'link'     => 'http://feeds.joomla.org/JoomlaAnnouncements',
				'ordering' => 1,
			),
			array(
				'name'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_NEWSFEEDS_NEWSFEED_1_NAME'),
				'link'     => 'http://feeds.joomla.org/JoomlaExtensions',
				'ordering' => 4,
			),
			array(
				'name'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_NEWSFEEDS_NEWSFEED_2_NAME'),
				'link'     => 'http://feeds.joomla.org/JoomlaSecurityNews',
				'ordering' => 2,
			),
			array(
				'name'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_NEWSFEEDS_NEWSFEED_3_NAME'),
				'link'     => 'http://feeds.joomla.org/JoomlaConnect',
				'ordering' => 3,
			),
		);
		$newsfeedsIds = array();

		foreach ($newsfeeds as $newsfeed)
		{
			// Set values which are always the same.
			$newsfeed['id']              = 0;
			$newsfeed['access']          = $access;
			$newsfeed['created_user_id'] = $user->id;
			$newsfeed['alias']           = ApplicationHelper::stringURLSafe($newsfeed['name']);
			$newsfeed['published']       = 1;
			$newsfeed['language']        = '*';
			$newsfeed['associations']    = array();
			$newsfeed['numarticles']     = 5;
			$newsfeed['cache_time']      = 3600;
			$newsfeed['rtl']             = 1;
			$newsfeed['description']     = '';
			$newsfeed['images']          = '';
			$newsfeed['catid']           = $catIdsLevel1[0];

			// Temporary, it should be fixed in other place
			$newsfeed['metakey']         = '';
			$newsfeed['metadesc']        = '';
			$newsfeed['xreference']      = '';
			$newsfeed['metadata']        = '{}';
			$newsfeed['params']          = '{}';

			try
			{
				if (!$model->save($newsfeed))
				{
					Factory::getLanguage()->load('com_newsfeeds');
					throw new Exception(Text::_($model->getError()));
				}
			}
			catch (Exception $e)
			{
				$response            = array();
				$response['success'] = false;
				$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 6, $e->getMessage());

				return $response;
			}

			// Get ID from category we just added
			$newsfeedsIds[] = $model->getState('newsfeed.id');
		}

		// Storing IDs in UserState for later usage.
		$this->app->setUserState('sampledata.testing.newsfeeds', $newsfeedsIds);
		$this->app->setUserState('sampledata.testing.newsfeeds.catids', $catIdsLevel1);

		$response            = array();
		$response['success'] = true;
		$response['message'] = Text::_('PLG_SAMPLEDATA_TESTING_STEP6_SUCCESS');

		return $response;
	}

	/**
	 * Seventh step to enter the sampledata. Menus.
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep7()
	{
		if ($this->app->input->get('type') !== $this->_name)
		{
			return;
		}

		if (!ComponentHelper::isEnabled('com_menus'))
		{
			$response            = array();
			$response['success'] = true;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_SKIPPED', 7, 'com_menus');

			return $response;
		}

		/** @var \Joomla\Component\Menus\Administrator\Model\MenuModel $model */
		$factory = $this->app->bootComponent('com_menus')->getMVCFactory();
		$model = $factory->createModel('Menu', 'Administrator', ['ignore_request' => true]);
		$modelItem = $factory->createModel('Item', 'Administrator', ['ignore_request' => true]);
		$menuTypes = array();

		for ($i = 0; $i <= 7; $i++)
		{
			$menu = array(
				'id'          => 0,
				'title'       => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_MENU_' . $i . '_TITLE'),
				'description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_MENU_' . $i . '_DESCRIPTION'),
			);

			// Calculate menutype.
			$menu['menutype'] = ApplicationHelper::stringURLSafe($menu['title']);

			try
			{
				$model->save($menu);
			}
			catch (Exception $e)
			{
				Factory::getLanguage()->load('com_menus');
				$response            = array();
				$response['success'] = false;
				$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 7, $e->getMessage());

				return $response;
			}

			$menuTypes[] = $menu['menutype'];
		}

		// Storing IDs in UserState for later usage.
		$this->app->setUserState('sampledata.testing.menutypes', $menuTypes);

		// Get previously entered Data from UserStates
		$contactIds      = $this->app->getUserState('sampledata.testing.contacts');
		$contactCatids1  = $this->app->getUserState('sampledata.testing.contacts.catids1');
		$contactCatids3  = $this->app->getUserState('sampledata.testing.contacts.catids3');
		$articleIds      = $this->app->getUserState('sampledata.testing.articles');
		$articleCatids1  = $this->app->getUserState('sampledata.testing.articles.catids1');
		$articleCatids2  = $this->app->getUserState('sampledata.testing.articles.catids2');
		$articleCatids3  = $this->app->getUserState('sampledata.testing.articles.catids3');
		$articleCatids4  = $this->app->getUserState('sampledata.testing.articles.catids4');
		$articleCatids5  = $this->app->getUserState('sampledata.testing.articles.catids5');
		$tagIds          = $this->app->getUserState('sampledata.testing.tags');
		$newsfeedsIds    = $this->app->getUserState('sampledata.testing.newsfeeds');
		$newsfeedsCatids = $this->app->getUserState('sampledata.testing.newsfeeds.catids');

		// Unset current "Home" menuitem since we set a new one.
		$menuItemTable = $modelItem->getTable();
		$menuItemTable->load(
			array(
				'home' => 1,
				'language' => '*',
			)
		);
		$menuItemTable->home = 0;
		$menuItemTable->store();

		// Insert first level of menuitems.
		$menuItems = array(
			array(
				'menutype'     => $menuTypes[0],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_0_TITLE'),
				'link'         => 'index.php?option=com_users&view=profile',
				'component_id' => ComponentHelper::getComponent('com_users')->id,
				'access'       => 2,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[1],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_1_TITLE'),
				'link'         => 'https://joomla.org',
				'type'         => 'url',
				'component_id' => 0,
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_2_TITLE'),
				'link'         => 'index.php?option=com_contact&view=contact&id=' . $contactIds[0],
				'component_id' => ComponentHelper::getComponent('com_contact')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[4],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_3_TITLE'),
				'link'         => 'index.php?option=com_users&view=login',
				'component_id' => ComponentHelper::getComponent('com_users')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[3],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_4_TITLE'),
				'link'              => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids3[1],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 114,
				'params'            => array(
					'show_description'       => 1,
					'show_description_image' => 1,
					'num_leading_articles'   => 1,
					'num_intro_articles'     => 4,
					'num_columns'            => 1,
					'num_links'              => 4,
					'show_pagination'        => 2,
					'show_feed_link'         => 1,
					'show_page_heading'      => 0,
					'secure'                 => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[4],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_5_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[37],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_category'        => 0,
					'show_parent_category' => 0,
					'show_author'          => 0,
					'show_create_date'     => 0,
					'show_modify_date'     => 0,
					'show_publish_date'    => 0,
					'show_item_navigation' => 0,
					'show_hits'            => 0,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[3],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_6_TITLE'),
				'link'              => 'index.php?option=com_content&view=form&layout=edit',
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'access'            => 3,
				'template_style_id' => 114,
				'params'            => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[3],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_7_TITLE'),
				'link'              => 'index.php?option=com_content&view=article&id=' . $articleIds[5],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 114,
				'params'            => array(
					'show_title'           => 0,
					'show_category'        => 0,
					'link_category'        => 0,
					'show_author'          => 0,
					'show_create_date'     => 0,
					'show_modify_date'     => 0,
					'show_publish_date'    => 0,
					'show_item_navigation' => 0,
					'show_print_icon'      => 0,
					'show_email_icon'      => 0,
					'show_hits'            => 0,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[3],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_8_TITLE'),
				'link'              => 'index.php?option=com_content&view=categories&id=' . $articleCatids3[2],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 114,
				'params'            => array(
					'show_base_description'  => 1,
					'drill_down_layout'      => 1,
					'show_description'       => 1,
					'show_description_image' => 1,
					'maxLevel'               => -1,
					'num_leading_articles'   => 1,
					'num_intro_articles'     => 4,
					'num_columns'            => 2,
					'num_links'              => 4,
					'show_page_heading'      => 0,
					'secure'                 => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_9_TITLE'),
				'link'         => 'index.php?option=com_contact&view=categories&id=' . $contactCatids1[0],
				'component_id' => ComponentHelper::getComponent('com_contact')->id,
				'params'       => array(
					'maxLevel'           => -1,
					'presentation_style' => 'sliders',
					'show_links'         => 1,
					'show_page_heading'  => 0,
					'secure'             => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_10_TITLE'),
				'link'         => 'index.php?option=com_newsfeeds&view=categories&id=0',
				'component_id' => ComponentHelper::getComponent('com_newsfeeds')->id,
				'params'       => array(
					'show_base_description'  => 1,
					'categories_description' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_10_PARAM_CATEGORIES_DESCRIPTION'),
					'maxLevel'               => -1,
					'show_empty_categories'  => 1,
					'show_description'       => 1,
					'show_description_image' => 1,
					'show_cat_num_articles'  => 1,
					'feed_character_count'   => 0,
					'show_page_heading'      => 0,
					'secure'                 => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_11_TITLE'),
				'link'         => 'index.php?option=com_newsfeeds&view=category&id=' . $newsfeedsCatids[0],
				'component_id' => ComponentHelper::getComponent('com_newsfeeds')->id,
				'params'       => array(
					'maxLevel'             => -1,
					'feed_character_count' => 0,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_12_TITLE'),
				'link'         => 'index.php?option=com_newsfeeds&view=newsfeed&id=' . $newsfeedsIds[0],
				'component_id' => ComponentHelper::getComponent('com_newsfeeds')->id,
				'params'       => array(
					'feed_character_count' => 0,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_14_TITLE'),
				'link'         => 'index.php?option=com_content&view=archive',
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_category'     => 1,
					'link_category'     => 1,
					'show_title'        => 1,
					'link_titles'       => 1,
					'show_intro'        => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_15_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[5],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_16_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids3[1],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_description'       => 0,
					'show_description_image' => 0,
					'num_leading_articles'   => 1,
					'num_intro_articles'     => 4,
					'num_columns'            => 2,
					'num_links'              => 4,
					'show_pagination'        => 2,
					'show_feed_link'         => 1,
					'show_page_heading'      => 0,
					'secure'                 => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_17_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&id=' . $articleCatids2[0],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'orderby_sec'       => 'alpha',
					'display_num'       => 10,
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_18_TITLE'),
				'link'         => 'index.php?option=com_content&view=featured',
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'num_leading_articles' => 1,
					'num_intro_articles'   => 4,
					'num_columns'          => 2,
					'num_links'            => 4,
					'multi_column_order'   => 1,
					'orderby_sec'          => 'front',
					'show_pagination'      => 2,
					'show_feed_link'       => 1,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_19_TITLE'),
				'link'         => 'index.php?option=com_content&view=form&layout=edit',
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'access'       => 3,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_20_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[9],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_21_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[57],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 1,
					'page_title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_21_PARAM_PAGE_TITLE'),
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_22_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[8],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_23_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[51],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_24_TITLE'),
				'link'         => 'index.php?option=com_content&view=categories&id=' . $articleCatids1[0],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'maxLevel'             => -1,
					'num_leading_articles' => 1,
					'num_intro_articles'   => 4,
					'num_columns'          => 2,
					'num_links'            => 4,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_25_TITLE'),
				'link'         => 'index.php?option=com_contact&view=category&id=' . $contactCatids3[0],
				'component_id' => ComponentHelper::getComponent('com_contact')->id,
				'params'       => array(
					'maxLevel'           => -1,
					'display_num'        => 20,
					'presentation_style' => 'sliders',
					'show_links'         => 1,
					'show_feed_link'     => 1,
					'show_page_heading'  => 0,
					'secure'             => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_26_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[38],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[52],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_title'           => 1,
					'link_titles'          => 0,
					'show_intro'           => 1,
					'show_category'        => 0,
					'show_parent_category' => 0,
					'show_author'          => 0,
					'show_create_date'     => 0,
					'show_modify_date'     => 0,
					'show_publish_date'    => 0,
					'show_item_navigation' => 0,
					'show_hits'            => 0,
					'show_noauth'          => 0,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_28_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[62],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_29_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[55],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_30_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[29],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_31_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[28],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_32_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[43],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_33_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[6],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_34_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[39],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_35_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[35],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_36_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[30],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_37_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[26],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_38_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[44],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_39_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[27],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_40_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[56],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_41_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[18],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_42_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[1],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_43_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[36],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_44_TITLE'),
				'link'         => 'index.php?option=com_users&view=login',
				'component_id' => ComponentHelper::getComponent('com_users')->id,
				'params'       => array(
					'logindescription_show'  => 1,
					'logoutdescription_show' => 1,
					'show_page_heading'      => 0,
					'secure'                 => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_45_TITLE'),
				'link'         => 'index.php?option=com_users&view=profile',
				'component_id' => ComponentHelper::getComponent('com_users')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_46_TITLE'),
				'link'         => 'index.php?option=com_users&view=profile&layout=edit',
				'component_id' => ComponentHelper::getComponent('com_users')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_47_TITLE'),
				'link'         => 'index.php?option=com_users&view=registration',
				'component_id' => ComponentHelper::getComponent('com_users')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_48_TITLE'),
				'link'         => 'index.php?option=com_users&view=remind',
				'component_id' => ComponentHelper::getComponent('com_users')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_49_TITLE'),
				'link'         => 'index.php?option=com_users&view=reset',
				'component_id' => ComponentHelper::getComponent('com_users')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_50_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[15],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_51_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&id=' . $articleCatids5[0],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'maxLevel'              => 0,
					'show_category_title'   => 1,
					'show_empty_categories' => 1,
					'show_description'      => 1,
					'display_num'           => 0,
					'show_headings'         => 0,
					'list_show_title'       => 1,
					'list_show_date'        => 0,
					'list_show_hits'        => 0,
					'list_show_author'      => 0,
					'orderby_sec'           => 'order',
					'show_category'         => 1,
					'link_category'         => 1,
					'show_page_heading'     => 0,
					'secure'                => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_52_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&id=' . $articleCatids5[1],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'maxLevel'            => 0,
					'show_category_title' => 1,
					'show_description'    => 1,
					'display_num'         => 0,
					'show_headings'       => 0,
					'list_show_title'     => 1,
					'list_show_hits'      => 0,
					'list_show_author'    => 0,
					'orderby_sec'         => 'order',
					'show_category'       => 1,
					'link_category'       => 1,
					'show_page_heading'   => 0,
					'secure'              => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_53_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[58],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_54_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[11],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[5],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_55_TITLE'),
				'link'              => 'index.php?option=com_contact&view=categories&id=' . $contactCatids3[1],
				'component_id'      => ComponentHelper::getComponent('com_contact')->id,
				'template_style_id' => 7,
				'params'            => array(
					'show_base_description'   => 1,
					'show_description'        => 1,
					'show_description_image'  => 1,
					'maxLevel'                => -1,
					'show_empty_categories'   => 1,
					'show_headings'           => 0,
					'show_email_headings'     => 0,
					'show_telephone_headings' => 0,
					'show_mobile_headings'    => 0,
					'show_fax_headings'       => 0,
					'show_suburb_headings'    => 0,
					'show_links'              => 1,
					'menu_text'               => 1,
					'show_page_heading'       => 0,
					'pageclass_sfx'           => ' categories-listalphabet',
					'secure'                  => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[5],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_56_TITLE'),
				'link'              => 'index.php?option=com_content&view=article&id=' . $articleIds[19],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 7,
				'params'            => array(
					'show_title'           => 0,
					'link_titles'          => 0,
					'show_intro'           => 1,
					'show_category'        => 0,
					'link_category'        => 0,
					'show_author'          => 0,
					'show_create_date'     => 0,
					'show_modify_date'     => 0,
					'show_publish_date'    => 0,
					'show_item_navigation' => 0,
					'show_icons'           => 0,
					'show_print_icon'      => 0,
					'show_email_icon'      => 0,
					'show_hits'            => 0,
					'menu_text'            => 1,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[5],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_57_TITLE'),
				'link'              => 'index.php?option=com_contact&view=category&id=' . $contactCatids3[0],
				'component_id'      => ComponentHelper::getComponent('com_contact')->id,
				'template_style_id' => 7,
				'params'            => array(
					'maxLevel'          => -1,
					'show_headings'     => 0,
					'show_links'        => 1,
					'show_feed_link'    => 1,
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[5],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_58_TITLE'),
				'link'              => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids3[3],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 7,
				'params'            => array(
					'layout_type'          => 'blog',
					'show_category_title'  => 1,
					'show_description'     => 1,
					'maxLevel'             => 0,
					'num_leading_articles' => 5,
					'num_intro_articles'   => 0,
					'num_columns'          => 1,
					'num_links'            => 4,
					'orderby_sec'          => 'alpha',
					'show_title'           => 1,
					'link_titles'          => 1,
					'show_intro'           => 1,
					'show_category'        => 0,
					'show_parent_category' => 0,
					'link_parent_category' => 0,
					'show_author'          => 0,
					'show_publish_date'    => 0,
					'show_hits'            => 0,
					'menu_text'            => 1,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[5],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_59_TITLE'),
				'link'              => 'index.php?option=com_users&view=login',
				'component_id'      => ComponentHelper::getComponent('com_users')->id,
				'template_style_id' => 7,
				'params'            => array(
					'logindescription_show'  => 1,
					'logoutdescription_show' => 1,
					'menu_text'              => 1,
					'show_page_heading'      => 0,
					'secure'                 => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[5],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_60_TITLE'),
				'link'              => 'index.php?option=com_content&view=article&id=' . $articleIds[12],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 7,
				'params'            => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[4],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_61_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[23],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_title'           => 1,
					'show_category'        => 0,
					'link_category'        => 0,
					'show_parent_category' => 0,
					'link_parent_category' => 0,
					'show_author'          => 0,
					'link_author'          => 0,
					'show_create_date'     => 0,
					'show_modify_date'     => 0,
					'show_publish_date'    => 0,
					'show_item_navigation' => 0,
					'show_icons'           => 0,
					'show_print_icon'      => 0,
					'show_email_icon'      => 0,
					'show_hits'            => 0,
					'menu_text'            => 1,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
				'home'         => 1,
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_62_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[21],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_title'           => 1,
					'link_titles'          => 0,
					'show_category'        => 0,
					'show_parent_category' => 0,
					'show_author'          => 0,
					'show_create_date'     => 0,
					'show_modify_date'     => 0,
					'show_publish_date'    => 0,
					'show_item_navigation' => 0,
					'show_hits'            => 0,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_63_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[2],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_64_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[25],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[4],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_65_TITLE'),
				'link'         => 'administrator',
				'type'         => 'url',
				'component_id' => 0,
				'params'       => array(),
			),
			array(
				'menutype'     => $menuTypes[0],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_66_TITLE'),
				'link'         => 'index.php?option=com_content&view=form&layout=edit',
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'access'       => 3,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_67_TITLE'),
				'link'         => 'index.php?option=com_contact&view=featured',
				'component_id' => ComponentHelper::getComponent('com_contact')->id,
				'params'       => array(
					'maxLevel'           => -1,
					'presentation_style' => 'sliders',
					'show_links'         => 1,
					'show_page_heading'  => 1,
					'secure'             => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_68_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[3],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 1,
					'secure'            => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[5],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_69_TITLE'),
				'link'              => 'index.php?option=com_content&view=form&layout=edit',
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'access'            => 4,
				'template_style_id' => 7,
				'params'            => array(
					'enable_category'   => 0,
					'catid'             => 14,
					'menu_text'         => 1,
					'show_page_heading' => 1,
					'secure'            => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[5],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_70_TITLE'),
				'link'              => 'index.php?option=com_content&view=category&id=' . $articleCatids3[4],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 7,
				'params'            => array(
					'show_category_title'   => 1,
					'show_description'      => 1,
					'maxLevel'              => 0,
					'show_empty_categories' => 0,
					'display_num'           => 10,
					'menu_text'             => 1,
					'show_page_heading'     => 0,
					'secure'                => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_71_TITLE'),
				'link'         => 'index.php?option=com_finder&view=search&q=&f=',
				'component_id' => ComponentHelper::getComponent('com_finder')->id,
				'params'       => array(
					'description_length' => 255,
					'allow_empty_query'  => 0,
					'show_feed'          => 0,
					'show_feed_text'     => 0,
					'menu_text'          => 1,
					'show_page_heading'  => 0,
					'secure'             => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_72_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[66],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_73_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[67],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[7],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_74_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[68],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_75_TITLE'),
				'link'         => 'index.php?option=com_tags&view=tag&layout=list&id[0]=' . $tagIds[2],
				'component_id' => ComponentHelper::getComponent('com_tags')->id,
				'params'       => array(
					'tag_list_item_maximum_characters' => 0,
					'maximum'                          => 200,
					'menu_text'                        => 1,
					'show_page_heading'                => 0,
					'secure'                           => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_76_TITLE'),
				'link'         => 'index.php?option=com_tags&view=tag&id[0]=' . $tagIds[1],
				'component_id' => ComponentHelper::getComponent('com_tags')->id,
				'params'       => array(
					'tag_list_item_maximum_characters' => 0,
					'maximum'                          => 200,
					'menu_text'                        => 1,
					'show_page_heading'                => 0,
					'secure'                           => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_77_TITLE'),
				'link'         => 'index.php?option=com_tags&view=tags',
				'component_id' => ComponentHelper::getComponent('com_tags')->id,
				'params'       => array(
					'tag_columns'                     => 4,
					'all_tags_tag_maximum_characters' => 0,
					'maximum'                         => 200,
					'menu_text'                       => 1,
					'show_page_heading'               => 0,
					'secure'                          => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_78_TITLE'),
				'link'         => 'index.php?option=com_config&view=config',
				'component_id' => ComponentHelper::getComponent('com_config')->id,
				'access'       => 6,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[6],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_79_TITLE'),
				'link'         => 'index.php?option=com_config&view=templates',
				'component_id' => ComponentHelper::getComponent('com_config')->id,
				'access'       => 6,
				'params'       => array(
					'menu_text'         => 1,
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
		);

		try
		{
			$menuIdsLevel1 = $this->addMenuItems($menuItems, 1);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 7, $e->getMessage());

			return $response;
		}

		// Insert alias menu items for level 1.
		$menuItems = array(
			array(
				'menutype'     => $menuTypes[1],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_100_TITLE'),
				'link'         => 'index.php?Itemid=',
				'type'         => 'alias',
				'component_id' => 0,
				'params'       => array(
					'aliasoptions' => $menuIdsLevel1[5],
				),
			),
			array(
				'menutype'     => $menuTypes[1],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_101_TITLE'),
				'link'         => 'index.php?Itemid=',
				'type'         => 'alias',
				'component_id' => 0,
				'params'       => array(
					'aliasoptions' => $menuIdsLevel1[61],
				),
			),
			array(
				'menutype'     => $menuTypes[1],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_102_TITLE'),
				'link'         => 'index.php?Itemid=',
				'type'         => 'alias',
				'component_id' => 0,
				'params'       => array(
					'aliasoptions' => $menuIdsLevel1[7],
					'menu_text'    => 1,
				),
			),
			array(
				'menutype'     => $menuTypes[1],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_103_TITLE'),
				'link'         => 'index.php?Itemid=',
				'type'         => 'alias',
				'component_id' => 0,
				'params'       => array(
					'aliasoptions' => $menuIdsLevel1[56],
					'menu_text'    => 1,
				),
			),
		);

		try
		{
			$menuIdsLevel1Alias = $this->addMenuItems($menuItems, 1);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 7, $e->getMessage());

			return $response;
		}

		// Insert second level of menuitems.
		$menuItems = array(
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_TITLE'),
				'link'         => 'index.php?option=com_content&view=categories&id=' . $articleCatids3[0],
				'parent_id'    => $menuIdsLevel1[27],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_base_description'     => 1,
					'maxLevelcat'               => 1,
					'show_empty_categories_cat' => 1,
					'show_subcat_desc_cat'      => 1,
					'show_cat_num_articles_cat' => 0,
					'show_description'          => 1,
					'show_description_image'    => 1,
					'maxLevel'                  => 1,
					'show_empty_categories'     => 1,
					'num_leading_articles'      => 1,
					'num_intro_articles'        => 4,
					'num_columns'               => 2,
					'num_links'                 => 4,
					'secure'                    => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[3],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_8_1_TITLE'),
				'link'              => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids4[5],
				'parent_id'         => $menuIdsLevel1[8],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 114,
				'params'            => array(
					'show_description'       => 1,
					'show_description_image' => 0,
					'num_leading_articles'   => 0,
					'num_intro_articles'     => 6,
					'num_columns'            => 2,
					'num_links'              => 4,
					'multi_column_order'     => 1,
					'show_pagination'        => 2,
					'show_intro'             => 0,
					'show_category'          => 1,
					'link_category'          => 1,
					'show_author'            => 0,
					'show_create_date'       => 0,
					'show_modify_date'       => 0,
					'show_publish_date'      => 0,
					'show_item_navigation'   => 1,
					'show_feed_link'         => 1,
					'show_page_heading'      => 0,
					'secure'                 => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[3],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_8_2_TITLE'),
				'link'              => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids4[5],
				'parent_id'         => $menuIdsLevel1[8],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 114,
				'params'            => array(
					'show_description'       => 0,
					'show_description_image' => 0,
					'num_leading_articles'   => 0,
					'num_intro_articles'     => 4,
					'num_columns'            => 2,
					'num_links'              => 4,
					'multi_column_order'     => 1,
					'show_pagination'        => 2,
					'show_intro'             => 0,
					'show_category'          => 1,
					'show_parent_category'   => 0,
					'link_parent_category'   => 0,
					'show_author'            => 0,
					'link_author'            => 0,
					'show_create_date'       => 0,
					'show_modify_date'       => 0,
					'show_publish_date'      => 0,
					'show_item_navigation'   => 1,
					'show_readmore'          => 1,
					'show_icons'             => 0,
					'show_print_icon'        => 0,
					'show_email_icon'        => 0,
					'show_hits'              => 0,
					'show_feed_link'         => 1,
					'show_page_heading'      => 0,
					'secure'                 => 0,
				),
			),
		);

		try
		{
			$menuIdsLevel2 = $this->addMenuItems($menuItems, 2);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 7, $e->getMessage());

			return $response;
		}

		// Insert alias menu items for level 2.
		$menuItems = array(
			array(
				'menutype'     => $menuTypes[4],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_103_TITLE'),
				'link'         => 'index.php?Itemid=',
				'type'         => 'alias',
				'parent_id'    => $menuIdsLevel1[5],
				'component_id' => 0,
				'params'       => array(
					'aliasoptions' => $menuIdsLevel1[7],
				),
			),
			array(
				'menutype'     => $menuTypes[4],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_104_TITLE'),
				'link'         => 'index.php?Itemid=',
				'type'         => 'alias',
				'parent_id'    => $menuIdsLevel1[5],
				'component_id' => 0,
				'params'       => array(
					'aliasoptions' => $menuIdsLevel1[56],
				),
			),
		);

		try
		{
			$menuIdsLevel2Alias = $this->addMenuItems($menuItems, 2);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 7, $e->getMessage());

			return $response;
		}

		// Insert third level of menuitems.
		$menuItems = array(
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&id=' . $articleCatids4[2],
				'parent_id'    => $menuIdsLevel2[0],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_description'      => 1,
					'maxLevel'              => 2,
					'show_empty_categories' => 1,
					'show_no_articles'      => '0',
					'show_subcat_desc'      => 1,
					'show_pagination_limit' => '0',
					'filter_field'          => 'hide',
					'show_headings'         => '0',
					'list_show_date'        => '0',
					'list_show_hits'        => '0',
					'list_show_author'      => '0',
					'show_pagination'       => '0',
					'show_title'            => 1,
					'link_titles'           => 1,
					'menu_text'             => 1,
					'page_title'            => 'Templates',
					'show_page_heading'     => 0,
					'secure'                => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_1_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids4[3],
				'parent_id'    => $menuIdsLevel2[0],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_description'       => 1,
					'show_description_image' => 1,
					'show_category_title'    => 1,
					'num_leading_articles'   => 1,
					'num_intro_articles'     => 4,
					'num_columns'            => 2,
					'num_links'              => 4,
					'show_page_heading'      => 0,
					'secure'                 => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_2_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids4[4],
				'parent_id'    => $menuIdsLevel2[0],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_description'     => 1,
					'show_category_title'  => 1,
					'num_leading_articles' => 0,
					'num_intro_articles'   => 7,
					'num_columns'          => 1,
					'num_links'            => 0,
					'orderby_sec'          => 'order',
					'show_category'        => 0,
					'link_category'        => 0,
					'show_parent_category' => 0,
					'link_parent_category' => 0,
					'show_author'          => 0,
					'show_create_date'     => 0,
					'show_modify_date'     => 0,
					'show_publish_date'    => 0,
					'show_icons'           => 0,
					'show_print_icon'      => 0,
					'show_email_icon'      => 0,
					'show_hits'            => 0,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
		);

		try
		{
			$menuIdsLevel3 = $this->addMenuItems($menuItems, 3);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 7, $e->getMessage());

			return $response;
		}

		// Insert fourth level of menuitems.
		$menuItems = array(
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_2_0_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[45],
				'parent_id'    => $menuIdsLevel3[2],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_2_1_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[4],
				'parent_id'    => $menuIdsLevel3[2],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_2_2_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[59],
				'parent_id'    => $menuIdsLevel3[2],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_2_3_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[13],
				'parent_id'    => $menuIdsLevel3[2],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_2_4_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[14],
				'parent_id'    => $menuIdsLevel3[2],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_2_5_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[40],
				'parent_id'    => $menuIdsLevel3[2],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_2_6_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[50],
				'parent_id'    => $menuIdsLevel3[2],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_7_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids5[6],
				'parent_id'    => $menuIdsLevel3[0],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_description'     => 1,
					'num_leading_articles' => 1,
					'num_intro_articles'   => 4,
					'num_columns'          => 2,
					'num_links'            => 4,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_8_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids5[5],
				'parent_id'    => $menuIdsLevel3[0],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_description'     => 1,
					'num_leading_articles' => 2,
					'num_intro_articles'   => 4,
					'num_columns'          => 2,
					'num_links'            => 4,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_9_TITLE'),
				'link'         => 'index.php?option=com_content&view=category&layout=blog&id=' . $articleCatids5[7],
				'parent_id'    => $menuIdsLevel3[0],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_description'     => 1,
					'num_leading_articles' => 1,
					'num_intro_articles'   => 4,
					'num_columns'          => 2,
					'num_links'            => 4,
					'show_page_heading'    => 1,
					'secure'               => 0,
				),
			),
		);

		try
		{
			$menuIdsLevel4 = $this->addMenuItems($menuItems, 4);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 7, $e->getMessage());

			return $response;
		}

		// Insert fifth level of menuitems.
		$menuItems = array(
			array(
				'menutype'          => $menuTypes[2],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_8_0_TITLE'),
				'link'              => 'index.php?option=com_content&view=article&id=' . $articleIds[48],
				'parent_id'         => $menuIdsLevel4[8],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 3,
				'params'            => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[2],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_8_1_TITLE'),
				'link'              => 'index.php?option=com_content&view=featured',
				'parent_id'         => $menuIdsLevel4[8],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 3,
				'params'            => array(
					'num_leading_articles' => 1,
					'num_intro_articles'   => 3,
					'num_columns'          => 3,
					'num_links'            => 0,
					'multi_column_order'   => 1,
					'orderby_sec'          => 'front',
					'show_pagination'      => 2,
					'show_feed_link'       => 1,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[2],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_7_2_TITLE'),
				'link'              => 'index.php?option=com_content&view=article&id=' . $articleIds[48],
				'parent_id'         => $menuIdsLevel4[7],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 4,
				'params'            => array(
					'show_page_heading' => 0,
					'secure'            => 0,
				),
			),
			array(
				'menutype'          => $menuTypes[2],
				'title'             => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_7_3_TITLE'),
				'link'              => 'index.php?option=com_content&view=featured',
				'parent_id'         => $menuIdsLevel4[7],
				'component_id'      => ComponentHelper::getComponent('com_content')->id,
				'template_style_id' => 4,
				'params'            => array(
					'num_leading_articles' => 1,
					'num_intro_articles'   => 3,
					'num_columns'          => 3,
					'num_links'            => 0,
					'multi_column_order'   => 1,
					'orderby_sec'          => 'front',
					'show_pagination'      => 2,
					'show_feed_link'       => 1,
					'show_page_heading'    => 0,
					'secure'               => 0,
				),

			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_9_4_TITLE'),
				'link'         => 'index.php?option=com_content&view=article&id=' . $articleIds[48],
				'parent_id'    => $menuIdsLevel4[9],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'show_page_heading' => 1,
					'secure'            => 0,
				),
			),
			array(
				'menutype'     => $menuTypes[2],
				'title'        => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MENUS_ITEM_27_0_0_9_5_TITLE'),
				'link'         => 'index.php?option=com_content&view=featured',
				'parent_id'    => $menuIdsLevel4[9],
				'component_id' => ComponentHelper::getComponent('com_content')->id,
				'params'       => array(
					'num_leading_articles' => 1,
					'num_intro_articles'   => 3,
					'num_columns'          => 3,
					'num_links'            => 0,
					'orderby_sec'          => 'front',
					'show_page_heading'    => 1,
					'secure'               => 0,
				),
			),
		);

		try
		{
			$menuIdsLevel5 = $this->addMenuItems($menuItems, 5);
		}
		catch (Exception $e)
		{
			$response            = array();
			$response['success'] = false;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 7, $e->getMessage());

			return $response;
		}

		$this->app->setUserState('sampledata.testing.menus.menuids1', $menuIdsLevel1);
		$this->app->setUserState('sampledata.testing.menus.menuids2', $menuIdsLevel2);
		$this->app->setUserState('sampledata.testing.menus.menuids3', $menuIdsLevel3);
		$this->app->setUserState('sampledata.testing.menus.menuids4', $menuIdsLevel4);
		$this->app->setUserState('sampledata.testing.menus.menuids5', $menuIdsLevel5);

		$response            = array();
		$response['success'] = true;
		$response['message'] = Text::_('PLG_SAMPLEDATA_TESTING_STEP7_SUCCESS');

		return $response;
	}

	/**
	 * Eighth step to enter the sampledata. Modules.
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  3.8.0
	 */
	public function onAjaxSampledataApplyStep8()
	{
		if ($this->app->input->get('type') !== $this->_name)
		{
			return;
		}

		if (!ComponentHelper::isEnabled('com_modules'))
		{
			$response            = array();
			$response['success'] = true;
			$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_SKIPPED', 8, 'com_modules');

			return $response;
		}

		$model = $this->app->bootComponent('com_modules')->getMVCFactory()->createModel('Module', 'Administrator', ['ignore_request' => true]);
		$access = (int) $this->app->get('access', 1);

		// Get previously entered Data from UserStates
		$menuTypes      = $this->app->getUserState('sampledata.testing.menutypes');
		$articleCatids1 = $this->app->getUserState('sampledata.testing.articles.catids1');
		$articleCatids2 = $this->app->getUserState('sampledata.testing.articles.catids2');
		$articleCatids3 = $this->app->getUserState('sampledata.testing.articles.catids3');
		$articleCatids4 = $this->app->getUserState('sampledata.testing.articles.catids4');
		$articleCatids5 = $this->app->getUserState('sampledata.testing.articles.catids5');
		$bannerCatids   = $this->app->getUserState('sampledata.testing.banners.catids');
		$menuIdsLevel1  = $this->app->getUserState('sampledata.testing.menus.menuids1');
		$menuIdsLevel2  = $this->app->getUserState('sampledata.testing.menus.menuids2');
		$menuIdsLevel5  = $this->app->getUserState('sampledata.testing.menus.menuids5');

		$modules = array(
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_0_TITLE'),
				'ordering' => 1,
				'position' => 'sidebar-right',
				'module'   => 'mod_menu',
				'access'   => $access,
				'params'   => array(
					'menutype'        => $menuTypes[4],
					'startLevel'      => 0,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'moduleclass_sfx' => '_menu',
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_1_TITLE'),
				'ordering'  => 1,
				'position'  => 'bottom-a',
				'module'    => 'mod_banners',
				'access'    => $access,
				'showtitle' => 0,
				'params'    => array(
					'target'      => 1,
					'count'       => 1,
					'cid'         => 3,
					'catid'       => array(),
					'tag_search'  => 0,
					'ordering'    => 0,
					'footer_text' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_1_FOOTEER_TEXT'),
					'cache'       => 1,
					'cache_time'  => 900,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_2_TITLE'),
				'ordering'   => 3,
				'position'   => 'sidebar-right',
				'module'     => 'mod_menu',
				'access'     => 2,
				'assignment' => -1,
				'assigned'   => array(
					$menuIdsLevel1[4],
					$menuIdsLevel1[6],
					$menuIdsLevel1[7],
					$menuIdsLevel1[8],
					$menuIdsLevel1[55],
					$menuIdsLevel1[56],
					$menuIdsLevel1[57],
					$menuIdsLevel1[58],
					$menuIdsLevel1[59],
					$menuIdsLevel1[60],
					$menuIdsLevel1[69],
					$menuIdsLevel1[70],
					$menuIdsLevel2[1],
					$menuIdsLevel2[2],
				),
				'params'     => array(
					'menutype'        => $menuTypes[0],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'moduleclass_sfx' => '_menu',
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_3_TITLE'),
				'ordering' => 1,
				'position' => 'menu',
				'module'   => 'mod_menu',
				'access'   => $access,
				'params'   => array(
					'menutype'        => $menuTypes[1],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'class_sfx'       => ' nav-pills',
					'cache'           => 0,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_4_TITLE'),
				'ordering'   => 2,
				'position'   => 'sidebar-left',
				'module'     => 'mod_menu',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[4],
					$menuIdsLevel1[5],
					$menuIdsLevel1[6],
					$menuIdsLevel1[7],
					$menuIdsLevel1[8],
					$menuIdsLevel2[1],
					$menuIdsLevel2[2],
				),
				'params'     => array(
					'menutype'        => $menuTypes[3],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'cache'           => 0,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_5_TITLE'),
				'ordering'   => 4,
				'position'   => 'sidebar-right',
				'module'     => 'mod_menu',
				'access'     => $access,
				'assignment' => -1,
				'assigned'   => array(
					$menuIdsLevel1[4],
					$menuIdsLevel1[5],
					$menuIdsLevel1[6],
					$menuIdsLevel1[7],
					$menuIdsLevel1[8],
					$menuIdsLevel1[55],
					$menuIdsLevel1[56],
					$menuIdsLevel1[57],
					$menuIdsLevel1[58],
					$menuIdsLevel1[59],
					$menuIdsLevel1[60],
					$menuIdsLevel1[69],
					$menuIdsLevel1[70],
					$menuIdsLevel2[1],
					$menuIdsLevel2[2],
				),
				'params'     => array(
					'menutype'        => $menuTypes[2],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'moduleclass_sfx' => '_menu',
					'cache'           => 0,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_6_TITLE'),
				'ordering'   => 1,
				'position'   => 'sitemapload',
				'module'     => 'mod_menu',
				'access'     => $access,
				'showtitle'  => 0,
				'assignment' => '-',
				'params'     => array(
					'menutype'        => $menuTypes[4],
					'startLevel'      => 2,
					'endLevel'        => 3,
					'showAllChildren' => 1,
					'class_sfx'       => 'sitemap',
					'cache'           => 0,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_7_TITLE'),
				'ordering'   => 5,
				'position'   => 'sidebar-right',
				'module'     => 'mod_menu',
				'access'     => $access,
				'assignment' => -1,
				'assigned'   => array(
					$menuIdsLevel1[4],
					$menuIdsLevel1[5],
					$menuIdsLevel1[6],
					$menuIdsLevel1[7],
					$menuIdsLevel1[8],
					$menuIdsLevel1[55],
					$menuIdsLevel1[56],
					$menuIdsLevel1[57],
					$menuIdsLevel1[58],
					$menuIdsLevel1[59],
					$menuIdsLevel1[60],
					$menuIdsLevel1[69],
					$menuIdsLevel1[70],
					$menuIdsLevel2[1],
					$menuIdsLevel2[2],
				),
				'params'     => array(
					'menutype'        => $menuTypes[4],
					'startLevel'      => 1,
					'endLevel'        => 1,
					'showAllChildren' => 0,
					'moduleclass_sfx' => '_menu',
					'cache'           => 0,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_8_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_articles_archive',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[42],
				),
				'params'     => array(
					'count'      => '10',
					'cache'      => 1,
					'cache_time' => 900,
					'cachemode'  => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_9_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_articles_latest',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[37],
				),
				'params'     => array(
					'catid'      => array($articleCatids2[0]),
					'count'      => 5,
					'ordering'   => 'c_dsc',
					'user_id'    => 0,
					'cache'      => 1,
					'cache_time' => 900,
					'cachemode'  => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_10_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_articles_popular',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[30],
				),
				'params'     => array(
					'catid'      => array($articleCatids2[1], $articleCatids2[2]),
					'count'      => 5,
					'show_front' => 1,
					'cache'      => 1,
					'cache_time' => 900,
					'cachemode'  => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_11_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_feed',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[50],
				),
				'params'     => array(
					'rssurl'      => 'https://community.joomla.org/blogs/community.feed?type=rss',
					'rssrtl'      => 0,
					'rsstitle'    => 1,
					'rssdesc'     => 1,
					'rssimage'    => 1,
					'rssitems'    => 3,
					'rssitemdesc' => 1,
					'word_count'  => 0,
					'cache'       => 1,
					'cache_time'  => 900,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_12_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_articles_news',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[36],
				),
				'params'     => array(
					'catid'             => array($articleCatids2[0]),
					'image'             => 0,
					'item_title'        => 0,
					'item_heading'      => 'h4',
					'showLastSeparator' => 1,
					'readmore'          => 1,
					'count'             => 1,
					'ordering'          => 'a.publish_up',
					'cache'             => 1,
					'cache_time'        => 900,
					'cachemode'         => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_13_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_random_image',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[35],
				),
				'params'     => array(
					'type'   => 'jpg',
					'folder' => 'images/sampledata/parks/animals',
					'width'  => 180,
					'cache'  => 0,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_14_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_related_items',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[43],
				),
				'params'     => array(
					'showDate' => 0,
					'owncache' => 1,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_15_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_search',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[34],
				),
				'params'     => array(
					'width'      => '20',
					'button_pos' => 'right',
					'opensearch' => 1,
					'cache'      => 1,
					'cache_time' => 900,
					'cachemode'  => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_16_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_stats',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[32],
				),
				'params'     => array(
					'serverinfo' => 1,
					'siteinfo'   => 1,
					'counter'    => 1,
					'increase'   => 0,
					'cache'      => 1,
					'cache_time' => 900,
					'cachemode'  => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_17_TITLE'),
				'ordering'   => 1,
				'position'   => 'syndicateload',
				'module'     => 'mod_syndicate',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[38],
				),
				'params'     => array(
					'text'   => 'Feed Entries',
					'format' => 'rss',
					'cache'  => 0,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_18_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_users_latest',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[28],
				),
				'params'     => array(
					'shownumber' => 5,
					'linknames'  => 0,
					'cache'      => 0,
					'cache_time' => 900,
					'cachemode'  => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_19_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_whosonline',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[29],
				),
				'params'     => array(
					'showmode'  => 2,
					'linknames' => 0,
					'cache'     => 0,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_20_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_wrapper',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[40],
				),
				'params'     => array(
					'url'         => 'https://www.youtube.com/embed/vb2eObvmvdI',
					'add'         => 1,
					'scrolling'   => 'auto',
					'width'       => '100%',
					'height'      => 390,
					'height_auto' => 1,
					'cache'       => 1,
					'cache_time'  => 900,
					'cachemode'   => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_21_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_footer',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[41],
				),
				'params'     => array(
					'cache'      => 1,
					'cache_time' => 900,
					'cachemode'  => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_22_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_login',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[39],
				),
				'params'     => array(
					'login'     => 280,
					'logout'    => 280,
					'greeting'  => 1,
					'name'      => 0,
					'usesecure' => 0,
					'cache'     => 0,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_23_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_menu',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[31],
				),
				'params'     => array(
					'menutype'        => $menuTypes[4],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'cache'           => 0,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_24_TITLE'),
				'ordering'   => 6,
				'position'   => 'sidebar-right',
				'module'     => 'mod_articles_latest',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[4],
					$menuIdsLevel1[6],
					$menuIdsLevel1[7],
					$menuIdsLevel1[8],
					$menuIdsLevel2[1],
					$menuIdsLevel2[2],
				),
				'params'     => array(
					'catid'      => array($articleCatids3[1]),
					'count'      => 5,
					'ordering'   => 'c_dsc',
					'user_id'    => 0,
					'show_front' => 1,
					'cache'      => 1,
					'cache_time' => 900,
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_25_TITLE'),
				'content'  => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_25_CONTENT'),
				'ordering' => 1,
				'module'   => 'mod_custom',
				'access'   => $access,
				'assigned' => array(
					$menuIdsLevel1[54],
				),
				'params'   => array(
					'prepare_content' => 1,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'static',
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_26_TITLE'),
				'ordering' => 1,
				'module'   => 'mod_breadcrumbs',
				'access'   => $access,
				'assigned' => array(
					$menuIdsLevel1[53],
				),
				'params'   => array(
					'showHere'   => 1,
					'showHome'   => 1,
					'homeText'   => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_26_HOMETEXT'),
					'showLast'   => 1,
					'cache'      => 0,
					'cache_time' => 900,
					'cachemode'  => 'itemid',
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_27_TITLE'),
				'ordering' => 1,
				'module'   => 'mod_banners',
				'access'   => $access,
				'assigned' => array(
					$menuIdsLevel1[33],
				),
				'params'   => array(
					'target'     => 1,
					'count'      => 1,
					'cid'        => 1,
					'catid'      => array($bannerCatids[0]),
					'tag_search' => 0,
					'ordering'   => 'random',
					'cache'      => 1,
					'cache_time' => 900,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_28_TITLE'),
				'ordering'   => 3,
				'position'   => 'sidebar-left',
				'module'     => 'mod_menu',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[5],
					$menuIdsLevel1[55],
					$menuIdsLevel1[56],
					$menuIdsLevel1[57],
					$menuIdsLevel1[58],
					$menuIdsLevel1[59],
					$menuIdsLevel1[60],
					$menuIdsLevel1[69],
					$menuIdsLevel1[70],
				),
				'params'     => array(
					'menutype'        => $menuTypes[5],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'cache'           => 0,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_29_TITLE'),
				'content'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_29_CONTENT'),
				'ordering'   => 1,
				'position'   => 'main-top',
				'module'     => 'mod_custom',
				'access'     => 4,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[55],
					$menuIdsLevel1[56],
					$menuIdsLevel1[57],
					$menuIdsLevel1[58],
					$menuIdsLevel1[59],
					$menuIdsLevel1[60],
					$menuIdsLevel1[69],
					$menuIdsLevel1[70],
				),
				'params'     => array(
					'prepare_content' => 1,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_30_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_articles_categories',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[63],
				),
				'params'     => array(
					'parent'           => 29,
					'show_description' => 0,
					'show_children'    => 0,
					'count'            => 0,
					'maxlevel'         => 0,
					'item_heading'     => 4,
					'owncache'         => 1,
					'cache_time'       => 900,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_31_TITLE'),
				'ordering'   => 3,
				'position'   => 'sidebar-left',
				'published'  => 0,
				'module'     => 'mod_languages',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[4],
					$menuIdsLevel1[6],
					$menuIdsLevel1[7],
					$menuIdsLevel1[8],
					$menuIdsLevel2[1],
					$menuIdsLevel2[2],
				),
				'params'     => array(
					'image'      => 1,
					'cache'      => 1,
					'cache_time' => 900,
					'cachemode'  => 'static',
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_32_TITLE'),
				'ordering' => 1,
				'position' => 'search',
				'module'   => 'mod_search',
				'access'   => $access,
				'params'   => array(
					'width'       => '20',
					'button_pos'  => 'right',
					'imagebutton' => 1,
					'cache'       => 1,
					'cache_time'  => 900,
					'cachemode'   => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_33_TITLE'),
				'ordering'   => 1,
				'position'   => 'languageswitcherload',
				'published'  => 0,
				'module'     => 'mod_languages',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[64],
				),
				'params'     => array(
					'image'      => 1,
					'cache'      => 1,
					'cache_time' => 900,
					'cachemode'  => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_34_TITLE'),
				'content'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_34_CONTENT'),
				'ordering'   => 1,
				'position'   => 'sidebar-left',
				'module'     => 'mod_custom',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[55],
					$menuIdsLevel1[56],
					$menuIdsLevel1[57],
					$menuIdsLevel1[58],
					$menuIdsLevel1[60],
					$menuIdsLevel1[69],
					$menuIdsLevel1[70],
				),
				'params'     => array(
					'prepare_content' => 1,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_35_TITLE'),
				'ordering'   => 2,
				'position'   => 'sidebar-right',
				'published'  => 0,
				'module'     => 'mod_menu',
				'access'     => $access,
				'assignment' => '-',
				'params'     => array(
					'menutype'        => $menuTypes[2],
					'startLevel'      => 1,
					'endLevel'        => 6,
					'showAllChildren' => 0,
					'class_sfx'       => '-menu',
					'cache'           => 0,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_36_TITLE'),
				'content'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_36_CONTENT'),
				'ordering'   => 2,
				'position'   => 'sidebar-left',
				'module'     => 'mod_custom',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[7],
				),
				'params'     => array(
					'prepare_content' => 1,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_37_TITLE'),
				'ordering'   => 1,
				'module'     => 'mod_articles_category',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[69],
				),
				'params'     => array(
					'mode'                         => 'normal',
					'show_on_article_page'         => 1,
					'show_front'                   => 'show',
					'count'                        => 0,
					'category_filtering_type'      => 1,
					'catid'                        => array($articleCatids4[5]),
					'show_child_category_articles' => 0,
					'levels'                       => 1,
					'author_filtering_type'        => 1,
					'created_by'                   => array(),
					'author_alias_filtering_type'  => 1,
					'created_by_alias'             => array(),
					'date_filtering'               => 'off',
					'date_field'                   => 'a.created',
					'relative_date'                => 30,
					'article_ordering'             => 'a.title',
					'article_ordering_direction'   => 'ASC',
					'article_grouping'             => 'none',
					'article_grouping_direction'   => 'ksort',
					'month_year_format'            => 'F Y',
					'item_heading'                 => 4,
					'link_titles'                  => 1,
					'show_date'                    => 0,
					'show_date_field'              => 'created',
					'show_date_format'             => 'Y-m-d H:i:s',
					'show_category'                => 0,
					'show_hits'                    => 0,
					'show_author'                  => 0,
					'show_introtext'               => 0,
					'introtext_limit'              => 100,
					'show_readmore'                => 0,
					'show_readmore_title'          => 1,
					'readmore_limit'               => 15,
					'owncache'                     => 1,
					'cache_time'                   => 900,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_38_TITLE'),
				'ordering'   => 1,
				'position'   => 'atomic-search',
				'module'     => 'mod_search',
				'access'     => $access,
				'showtitle'  => 0,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel5[0],
					$menuIdsLevel5[1],
				),
				'params'     => array(
					'width'      => 20,
					'button_pos' => 'right',
					'cache'      => 1,
					'cache_time' => 900,
					'cachemode'  => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_39_TITLE'),
				'ordering'   => 1,
				'position'   => 'atomic-topmenu',
				'module'     => 'mod_menu',
				'access'     => $access,
				'showtitle'  => 0,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel5[0],
					$menuIdsLevel5[1],
				),
				'params'     => array(
					'menutype'        => $menuTypes[2],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_40_TITLE'),
				'content'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_40_CONTENT'),
				'ordering'   => 1,
				'position'   => 'atomic-topquote',
				'module'     => 'mod_custom',
				'access'     => $access,
				'showtitle'  => 0,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel5[0],
					$menuIdsLevel5[1],
				),
				'params'     => array(
					'prepare_content' => 1,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_41_TITLE'),
				'content'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_41_CONTENT'),
				'ordering'   => 1,
				'position'   => 'atomic-bottomleft',
				'module'     => 'mod_custom',
				'access'     => $access,
				'showtitle'  => 0,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel5[0],
					$menuIdsLevel5[1],
				),
				'params'     => array(
					'prepare_content' => 1,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_42_TITLE'),
				'content'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_42_CONTENT'),
				'ordering'   => 1,
				'position'   => 'atomic-bottommiddle',
				'module'     => 'mod_custom',
				'access'     => $access,
				'showtitle'  => 0,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel5[0],
					$menuIdsLevel5[1],
				),
				'params'     => array(
					'prepare_content' => 1,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_43_TITLE'),
				'content'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_43_CONTENT'),
				'ordering'   => 1,
				'position'   => 'atomic-sidebar',
				'module'     => 'mod_custom',
				'access'     => $access,
				'showtitle'  => 0,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel5[0],
					$menuIdsLevel5[1],
				),
				'params'     => array(
					'prepare_content' => 1,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'static',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_44_TITLE'),
				'ordering'   => 2,
				'position'   => 'atomic-sidebar',
				'module'     => 'mod_login',
				'access'     => $access,
				'showtitle'  => 0,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel5[0],
					$menuIdsLevel5[1],
				),
				'params'     => array(
					'greeting'  => 1,
					'name'      => 0,
					'usesecure' => 0,
					'cache'     => 0,
				),
			),
			array(
				'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_45_TITLE'),
				'ordering'  => 1,
				'position'  => 'bottom-a',
				'module'    => 'mod_banners',
				'access'    => $access,
				'showtitle' => 0,
				'params'    => array(
					'target'      => 1,
					'count'       => 1,
					'cid'         => 2,
					'catid'       => array($bannerCatids[0]),
					'tag_search'  => 0,
					'ordering'    => 0,
					'footer_text' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_45_FOOTER_TEXT'),
					'cache'       => 1,
					'cache_time'  => 900,
				),
			),
			array(
				'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_46_TITLE'),
				'ordering'  => 1,
				'position'  => 'bottom-a',
				'module'    => 'mod_banners',
				'access'    => $access,
				'showtitle' => 0,
				'params'    => array(
					'target'      => 1,
					'count'       => 1,
					'cid'         => 1,
					'catid'       => array($bannerCatids[0]),
					'tag_search'  => 0,
					'ordering'    => 0,
					'footer_text' => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_46_FOOTER_TEXT'),
					'cache'       => 1,
					'cache_time'  => 900,
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_47_TITLE'),
				'ordering'   => 2,
				'module'     => 'mod_finder',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[72],
				),
				'params'     => array(
					'show_autosuggest' => 1,
					'show_advanced'    => 0,
					'field_size'       => array(20),
					'show_label'       => 0,
					'label_pos'        => 'top',
					'show_button'      => 0,
					'button_pos'       => 'right',
					'opensearch'       => 1,
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_48_TITLE'),
				'ordering' => '2',
				'position' => 'sidebar-right',
				'module'   => 'mod_menu',
				'access'   => $access,
				'params'   => array(
					'menutype'        => $menuTypes[6],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_49_TITLE'),
				'ordering' => 1,
				'position' => 'sidebar-left',
				'module'   => 'mod_menu',
				'access'   => $access,
				'params'   => array(
					'menutype'        => $menuTypes[7],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'cache'           => 1,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'      => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_50_TITLE'),
				'ordering'   => 1,
				'position'   => 'sidebar-right',
				'module'     => 'mod_menu',
				'access'     => $access,
				'assignment' => 1,
				'assigned'   => array(
					$menuIdsLevel1[5],
					$menuIdsLevel1[55],
					$menuIdsLevel1[56],
					$menuIdsLevel1[57],
					$menuIdsLevel1[58],
					$menuIdsLevel1[59],
					$menuIdsLevel1[60],
					$menuIdsLevel1[69],
					$menuIdsLevel1[70],
				),
				'params'     => array(
					'menutype'        => $menuTypes[5],
					'startLevel'      => 1,
					'endLevel'        => 0,
					'showAllChildren' => 0,
					'cache'           => 0,
					'cache_time'      => 900,
					'cachemode'       => 'itemid',
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_51_TITLE'),
				'ordering' => 1,
				'module'   => 'mod_tags_popular',
				'access'   => $access,
				'params'   => array(
					'maximum'         => 5,
					'timeframe'       => 'alltime',
					'order_value'     => 'count',
					'order_direction' => 1,
					'display_count'   => 0,
					'no_results_text' => 0,
					'minsize'         => 1,
					'maxsize'         => 2,
					'owncache'        => 1,
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_52_TITLE'),
				'ordering' => 1,
				'module'   => 'mod_tags_similar',
				'access'   => $access,
				'params'   => array(
					'maximum'   => 5,
					'matchtype' => 'any',
					'owncache'  => 1,
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_53_TITLE'),
				'ordering' => 1,
				'position' => 'sidebar-left',
				'module'   => 'mod_syndicate',
				'access'   => $access,
				'params'   => array(
					'display_text' => 1,
					'text'         => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_53_TEXT'),
					'format'       => 'rss',
					'cache'        => 0,
				),
			),
			array(
				'title'    => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_54_TITLE'),
				'ordering' => 1,
				'position' => 'sidebar-left',
				'module'   => 'mod_tags_similar',
				'access'   => $access,
				'params'   => array(
					'maximum'   => 5,
					'matchtype' => 'any',
					'owncache'  => 1,
				),
			),
			// Admin modules
			array(
				'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_55_TITLE'),
				'content'   => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_55_CONTENT'),
				'ordering'  => 5,
				'position'  => 'cpanel',
				'module'    => 'mod_custom',
				'access'    => $access,
				'params'    => array(
					'prepare_content' => 1,
					'cache'           => 1,
					'cache_time'      => 900,
					'bootstrap_size'  => 6,
				),
				'client_id' => 1,
			),
			array(
				'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_56_TITLE'),
				'ordering'  => 6,
				'position'  => 'cpanel',
				'published' => 0,
				'module'    => 'mod_feed',
				'access'    => $access,
				'params'    => array(
					'rssurl'         => 'http://feeds.joomla.org/JoomlaAnnouncements',
					'rssrtl'         => 0,
					'rsstitle'       => 1,
					'rssdesc'        => 1,
					'rssimage'       => 1,
					'rssitems'       => 3,
					'rssitemdesc'    => 1,
					'word_count'     => 0,
					'cache'          => 1,
					'cache_time'     => 900,
					'bootstrap_size' => 6,
				),
				'client_id' => 1,
			),
			array(
				'title'     => Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_MODULES_MODULE_57_TITLE'),
				'ordering'  => 3,
				'position'  => 'cpanel',
				'module'    => 'mod_stats_admin',
				'access'    => $access,
				'params'    => array(
					'serverinfo'     => 1,
					'siteinfo'       => 1,
					'counter'        => 1,
					'increase'       => 0,
					'cache'          => 1,
					'cache_time'     => 900,
					'cachemode'      => 'static',
					'bootstrap_size' => 6,
				),
				'client_id' => 1,
			),
			/*
			TODO: Altering existing admin modules (Bootstrap Size).
			array(
				'title'            => 'Popular Articles',
				'position'         => 'cpanel',
				'module'           => 'mod_popular',
				'params'           => array(
					'bootstrap_size'  => 6,
				),
			),
			array(
				'title'            => 'Logged-in Users',
				'position'         => 'cpanel',
				'module'           => 'mod_logged',
				'params'           => array(
					'bootstrap_size'  => 6,
				),
			),
			array(
				'title'            => 'Recently Added Articles',
				'position'         => 'cpanel',
				'module'           => 'mod_latest',
				'params'           => array(
					'bootstrap_size'  => 6,
				),
			),
			*/
		);

		foreach ($modules as $module)
		{
			// Set values which are always the same.
			$module['id']              = 0;
			$module['asset_id']        = 0;
			$module['language']        = '*';
			$module['description']     = '';

			if (!isset($module['published']))
			{
				$module['published'] = 1;
			}

			if (!isset($module['note']))
			{
				$module['note'] = '';
			}

			if (!isset($module['content']))
			{
				$module['content'] = '';
			}

			if (!isset($module['showtitle']))
			{
				$module['showtitle'] = 1;
			}

			if (!isset($module['position']))
			{
				$module['position'] = '';
			}

			if (!isset($module['params']))
			{
				$module['params'] = array();
			}

			if (!isset($module['client_id']))
			{
				$module['client_id'] = 0;
			}

			if (!isset($module['assignment']))
			{
				$module['assignment'] = 0;
			}

			if (!$model->save($module))
			{
				Factory::getLanguage()->load('com_modules');
				$response            = array();
				$response['success'] = false;
				$response['message'] = Text::sprintf('PLG_SAMPLEDATA_TESTING_STEP_FAILED', 8, Text::_($model->getError()));

				return $response;
			}
		}

		$response            = array();
		$response['success'] = true;
		$response['message'] = Text::_('PLG_SAMPLEDATA_TESTING_STEP8_SUCCESS');

		return $response;
	}

	/**
	 * Final step to show completion of sampledata.
	 *
	 * @return  array|void  Will be converted into the JSON response to the module.
	 *
	 * @since  4.0.0
	 */
	public function onAjaxSampledataApplyStep9()
	{
		$response['success'] = true;
		$response['message'] = Text::_('PLG_SAMPLEDATA_TESTING_STEP9_SUCCESS');

		return $response;
	}

	/**
	 * Adds categories.
	 *
	 * @param   array    $categories  Array holding the category arrays.
	 * @param   string   $extension   Name of the extension.
	 * @param   integer  $level       Level in the category tree.
	 *
	 * @return  array  IDs of the inserted categories.
	 *
	 * @throws  Exception
	 *
	 * @since  3.8.0
	 */
	private function addCategories(array $categories, $extension, $level)
	{
		if (!$this->categoryModel)
		{
			$this->categoryModel = $this->app->bootComponent('com_categories')
				->getMVCFactory()
				->createModel('Category', 'Administrator', ['ignore_request' => true]);
		}

		$catIds = array();
		$access = (int) $this->app->get('access', 1);
		$user   = Factory::getUser();

		foreach ($categories as $category)
		{
			// Set values which are always the same.
			$category['id']              = 0;
			$category['published']       = 1;
			$category['access']          = $access;
			$category['created_user_id'] = $user->id;
			$category['extension']       = $extension;
			$category['level']           = $level;
			$category['alias']           = ApplicationHelper::stringURLSafe($category['title']);
			$category['associations']    = array();
			$category['params']          = array();

			// Set description to empty if not set
			if (!isset($category['description']))
			{
				$category['description'] = '';
			}

			// Language defaults to "All" (*) when not set
			if (!isset($category['language']))
			{
				$category['language'] = '*';
			}

			if (!$this->categoryModel->save($category))
			{
				throw new Exception($this->categoryModel->getError());
			}

			// Get ID from category we just added
			$catIds[] = $this->categoryModel->getState($this->categoryModel->getName() . '.id');
		}

		return $catIds;
	}

	/**
	 * Adds articles.
	 *
	 * @param   array  $articles  Array holding the category arrays.
	 *
	 * @return  array  IDs of the inserted categories.
	 *
	 * @throws  Exception
	 *
	 * @since  3.8.0
	 */
	private function addArticles(array $articles)
	{
		$ids = array();

		$access = (int) $this->app->get('access', 1);
		$user   = Factory::getUser();
		$mvcFactory = $this->app->bootComponent('com_content')->getMVCFactory();

		foreach ($articles as $i => $article)
		{
			/** @var \Joomla\Component\Content\Administrator\Model\ArticleModel $model */
			$model = $mvcFactory->createModel('Article', 'Administrator', ['ignore_request' => true]);

			// Set values from language strings.
			$article['title']     = Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_ARTICLE_' . str_pad($i, 2, '0', STR_PAD_LEFT) . '_TITLE');
			$article['introtext'] = Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_ARTICLE_' . str_pad($i, 2, '0', STR_PAD_LEFT) . '_INTROTEXT');
			$article['fulltext']  = Text::_('PLG_SAMPLEDATA_TESTING_SAMPLEDATA_CONTENT_ARTICLE_' . str_pad($i, 2, '0', STR_PAD_LEFT) . '_FULLTEXT');

			// Set values which are always the same.
			$article['id']              = 0;
			$article['access']          = $access;
			$article['created_user_id'] = $user->id;
			$article['alias']           = ApplicationHelper::stringURLSafe($article['title']);
			$article['language']        = '*';
			$article['associations']    = array();
			$article['metakey']         = '';
			$article['metadesc']        = '';
			$article['xreference']      = '';

			// Set article to published if not set.
			if (!isset($article['state']))
			{
				$article['state'] = 1;
			}

			// Set article to not featured if not set.
			if (!isset($article['featured']))
			{
				$article['featured'] = 0;
			}

			// Set images to empty if not set.
			if (!isset($article['images']))
			{
				$article['images'] = '';
			}
			// JSON Encode it when set.
			else
			{
				$article['images'] = json_encode($article['images']);
			}

			if (!$model->save($article))
			{
				Factory::getLanguage()->load('com_content');
				throw new Exception(Text::_($model->getError()));
			}

			// Get ID from category we just added
			$ids[] = $model->getState($model->getName() . '.id');
		}

		return $ids;
	}

	/**
	 * Adds menuitems.
	 *
	 * @param   array    $menuItems  Array holding the menuitems arrays.
	 * @param   integer  $level      Level in the category tree.
	 *
	 * @return  array  IDs of the inserted menuitems.
	 *
	 * @throws  Exception
	 *
	 * @since  3.8.0
	 */
	private function addMenuItems(array $menuItems, $level)
	{
		if (!$this->menuItemModel)
		{
			$this->menuItemModel = $this->app->bootComponent('com_menus')
				->getMVCFactory()
				->createModel('Item', 'Administrator', ['ignore_request' => true]);
		}

		$itemIds = array();
		$access  = (int) $this->app->get('access', 1);
		$user    = Factory::getUser();

		foreach ($menuItems as $menuItem)
		{
			// Reset item.id in model state.
			$this->menuItemModel->setState('item.id', 0);

			// Set values which are always the same.
			$menuItem['id']              = 0;
			$menuItem['created_user_id'] = $user->id;
			$menuItem['alias']           = ApplicationHelper::stringURLSafe($menuItem['title']);
			$menuItem['published']       = 1;
			$menuItem['language']        = '*';
			$menuItem['note']            = '';
			$menuItem['img']             = '';
			$menuItem['browserNav']      = 0;
			$menuItem['associations']    = array();
			$menuItem['client_id']       = 0;
			$menuItem['level']           = $level;

			// Set access to default if not set
			if (!isset($menuItem['access']))
			{
				$menuItem['access'] = $access;
			}

			// Set type to 'component' if not set
			if (!isset($menuItem['type']))
			{
				$menuItem['type'] = 'component';
			}

			// Set template_style_id to global if not set
			if (!isset($menuItem['template_style_id']))
			{
				$menuItem['template_style_id'] = 0;
			}

			// Set home if not set
			if (!isset($menuItem['home']))
			{
				$menuItem['home'] = 0;
			}

			// Set parent_id to root (1) if not set
			if (!isset($menuItem['parent_id']))
			{
				$menuItem['parent_id'] = 1;
			}

			if (!$this->menuItemModel->save($menuItem))
			{
				// Try two times with another alias (-1 and -2).
				$menuItem['alias'] .= '-1';

				if (!$this->menuItemModel->save($menuItem))
				{
					$menuItem['alias'] = substr_replace($menuItem['alias'], '2', -1);

					if (!$this->menuItemModel->save($menuItem))
					{
						throw new Exception($menuItem['title'] . ' => ' . $menuItem['alias'] . ' : ' . $this->menuItemModel->getError());
					}
				}
			}

			// Get ID from menuitem we just added
			$itemIds[] = $this->menuItemModel->getstate('item.id');
		}

		return $itemIds;
	}
}
