<?php
$searchCategories=$searchCategories??rows('SELECT id,parent_id,name FROM categories WHERE active=1 ORDER BY sort_order,name');
$searchQuery=mb_substr(trim((string)($searchQuery??'')),0,150);
$searchCategoryId=max(0,(int)($searchCategoryId??0));
$searchVariant=($searchVariant??'home')==='shop'?'shop':'home';
$searchId='product-search-'.$searchVariant;
?>
<section class="home-product-search home-product-search--<?= h($searchVariant) ?>" aria-label="Product search">
 <div class="<?= $searchVariant==='home'?'container':'home-search-shell' ?>">
  <form class="home-search-form" action="<?= h(url('shop.php')) ?>" method="get" role="search" data-product-search data-search-endpoint="<?= h(url('ajax/search_products.php')) ?>">
   <label class="visually-hidden" for="<?= h($searchId) ?>-category">Product category</label>
   <select class="home-search-category" id="<?= h($searchId) ?>-category" name="category" aria-label="Product category">
    <option value="">All Categories</option>
    <?php foreach($searchCategories as $searchCategory): ?><option value="<?= (int)$searchCategory['id'] ?>" <?= $searchCategoryId===(int)$searchCategory['id']?'selected':'' ?>><?= !empty($searchCategory['parent_id'])?'— ':'' ?><?= h(html_entity_decode($searchCategory['name'],ENT_QUOTES,'UTF-8')) ?></option><?php endforeach; ?>
   </select>
   <div class="home-search-field">
    <i class="far fa-search" aria-hidden="true"></i>
    <label class="visually-hidden" for="<?= h($searchId) ?>-input">Search products</label>
    <input class="home-search-input" id="<?= h($searchId) ?>-input" name="q" type="search" value="<?= h($searchQuery) ?>" placeholder="Search medical equipment, supplies, brands..." maxlength="150" autocomplete="off" enterkeyhint="search" aria-autocomplete="list" aria-controls="<?= h($searchId) ?>-suggestions" aria-expanded="false">
    <div class="home-search-suggestions" id="<?= h($searchId) ?>-suggestions" role="listbox" hidden></div>
   </div>
   <?php if(!empty($searchSort)): ?><input type="hidden" name="sort" value="<?= h($searchSort) ?>"><?php endif; ?>
   <button class="home-search-button" type="submit" aria-label="Search products"><i class="far fa-search" aria-hidden="true"></i><span>Search</span></button>
  </form>
 </div>
</section>
