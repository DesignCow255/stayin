<?php
namespace App\Controllers;
use App\Core\{Controller,Request,Response};
use App\Repositories\SearchRepository;
final class SearchController extends Controller {
 public function index(Request $r):Response {return $this->view('search',['metaTitle'=>'Find your next stay · StayIn',...SearchRepository::search($r->query(),max(1,$r->int('page',1)))]);}
}
