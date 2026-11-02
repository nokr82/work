<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ----- 사장님 화면 (모바일) -----
$routes->get('/', 'Home::index');

$routes->get('leads', 'Leads::index');
$routes->get('leads/kanban', 'Leads::kanban');              // PC 칸반
$routes->get('leads/new', 'Leads::new');                    // 전화 문의 등록 폼
$routes->post('leads/create', 'Leads::create');
$routes->get('leads/(:num)', 'Leads::show/$1');
$routes->post('leads/(:num)/stage', 'Leads::stage/$1');     // 단계 변경
$routes->post('leads/(:num)/move', 'Leads::move/$1');       // 칸반 드래그 (JSON)
$routes->post('leads/(:num)/memo', 'Leads::memo/$1');

$routes->get('quotes/edit', 'Quotes::latest');              // 하단 '견적' 탭
$routes->get('quotes/new/(:num)', 'Quotes::create/$1');
$routes->get('quotes/edit/(:num)', 'Quotes::edit/$1');
$routes->post('quotes/(:num)/items', 'Quotes::addItem/$1');
$routes->post('quotes/(:num)/items/(:num)/qty', 'Quotes::qty/$1/$2');
$routes->post('quotes/(:num)/discount', 'Quotes::discount/$1');
$routes->post('quotes/(:num)/send', 'Quotes::send/$1');

// ----- 고객 화면 (로그인 없음) -----
$routes->get('inquiry', 'Inquiry::form');
$routes->post('inquiry/step/(:num)', 'Inquiry::save/$1');
$routes->post('inquiry/submit', 'Inquiry::submit');
$routes->get('inquiry/done', 'Inquiry::done');

$routes->get('q/(:segment)', 'Quotes::publicView/$1');      // 견적 공유 링크
$routes->post('q/(:segment)/accept', 'Quotes::accept/$1');
$routes->post('q/(:segment)/revise', 'Quotes::revise/$1');

// ----- 사장님 화면 (PC) -----
$routes->get('prices', 'Prices::index');
$routes->post('prices/item', 'Prices::saveItem');
$routes->post('prices/item/(:num)/toggle', 'Prices::toggle/$1');
$routes->post('prices/category', 'Prices::addCategory');
