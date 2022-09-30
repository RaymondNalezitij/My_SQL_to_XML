<?php

class mySQLFunctions
{
    private mysqli $mySql;

    public function __construct() {
        $this->mySql = new mysqli("localhost", "user", "password", "webdev-homework");
    }

    public function getProductName($product_id, $language_id): string {
        $sql = mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT name FROM product_description where product_id = {$product_id} and language_id = {$language_id}"
        ));
        return $sql['name'];
    }

    public function getProductDescription($product_id, $language_id): string {
        $sql = mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT meta_description FROM product_description where product_id = {$product_id} and language_id = {$language_id}"
        ));
        if (strlen($sql['meta_description']) > 200) {
            while (strlen($sql['meta_description']) > 200) {
                $words = explode(" ", $sql['meta_description']);
                array_splice($words, -1);
                $sql['meta_description'] = implode(" ", $words);
            }
            return $sql['meta_description'] . "...";
        } else {
            return $sql['meta_description'];
        }
    }

    public function checkImageLink($image_id): string {
        if ($image_id !== "") {
            return "https://www.webdev.lv/" . $image_id;
        } else {
            return "";
        }
    }

    public function convertDate($date): string {
        $dateArray = explode(" ", $date);
        $reverseDateArray = explode("-", $dateArray[0]);
        $year = $reverseDateArray[0];
        $reverseDateArray[0] = $reverseDateArray[2];
        $z[2] = $year;
        return implode("-", $z);
    }

    public function getSpecialPrice($product_id): string {
        $sql = mysqli_query(
            $this->mySql,
            "SELECT price, date_end FROM product_special where product_id = {$product_id}"
        );
        if ($sql->num_rows == 0) {
            return "";
        } else {
            $sql = mysqli_fetch_array(mysqli_query(
                $this->mySql,
                "SELECT price, date_end FROM product_special where product_id = {$product_id}"
            ));
            if (strtotime(date('y-m-d')) <= strtotime($sql['date_end'])) {
                return number_format($sql["price"] + ($sql["price"] / 100 * 21), 2, ".");
            } else {
                return "";
            }
        }
    }

    public function getCategoryNumber($product_id): array{
        return mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT category_id FROM product_to_category where product_id = {$product_id}"
        ));
    }

    public function getProductCategory($category_id): string {

        $category_name = mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT name FROM category_description where category_id = {$category_id["category_id"]} and language_id = 1"
        ));

        return $category_name["name"];
    }

    public function getFullProductCategory($category_id): string {

        $full_category = [$this->getProductCategory($category_id)];

        $parent_id = mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT parent_id FROM category where category_id = {$category_id["category_id"]}"
        ));

        while($parent_id['parent_id'] !== "0"){
            $category = mysqli_fetch_array(mysqli_query(
                $this->mySql,
                "SELECT name FROM category_description where category_id = {$parent_id["parent_id"]} and language_id = 1"
            ));

            $full_category[] = $category['name'];

            $parent_id = mysqli_fetch_array(mysqli_query(
                $this->mySql,
                "SELECT parent_id FROM category where category_id = {$parent_id["parent_id"]}"
            ));
        }
        return(implode(" >> ",array_reverse($full_category)));
    }

    public function getMySql(): mysqli
    {
        return $this->mySql;
    }
}

$mySql = new mySQLFunctions;

$product = mysqli_query(
    $mySql->getMySql(),
    "SELECT * FROM product",
);

$xml = "<root>";
while ($repository = mysqli_fetch_array($product)) {
    $xml .= "<item>";
    $xml .= "<model>" . $repository["model"] . "</model>";
    $xml .= "<status>" . $repository["status"] . "</status>";
    $xml .= "<name>";
    $xml .= "<lv>" . $mySql->getProductName($repository["product_id"], 1) . "</lv>";
    $xml .= "<en>" . $mySql->getProductName($repository["product_id"], 2) . "</en>";
    $xml .= "<ru>" . $mySql->getProductName($repository["product_id"], 3) . "</ru>";
    $xml .= "</name>";
    $xml .= "<description>";
    $xml .= "<lv>" . $mySql->getProductDescription($repository["product_id"], 1) . "</lv>";
    $xml .= "<en>" . $mySql->getProductDescription($repository["product_id"], 2) . "</en>";
    $xml .= "<ru>" . $mySql->getProductDescription($repository["product_id"], 3) . "</ru>";
    $xml .= "</description>";
    $xml .= "<quantity>" . $repository["quantity"] . "</quantity>";
    $xml .= "<ean>" . $repository["ean"] . "</ean>";
    $xml .= "<image>" . $mySql->checkImageLink($repository["image"]) . "</image>";
    $xml .= "<date_added>" . $mySql->convertDate($repository["date_added"]) . "</date_added>";
    $xml .= "<price>" . number_format($repository["price"] + ($repository["price"] / 100 * 21), 2, ".") . "</price>";
    $xml .= "<special_price>" . $mySql->getSpecialPrice($repository["product_id"]) . "</special_price>";
    $xml .= "<category>" . $mySql->getProductCategory($mySql->getCategoryNumber($repository["product_id"])) . "</category>";
    $xml .= "<full_category>" . htmlspecialchars_decode($mySql->getFullProductCategory($mySql->getCategoryNumber($repository["product_id"]))) . "</full_category>";
    $xml .= "</item>";
}
$xml .= "</root>";

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->loadXML($xml);
$dom->formatOutput = true;
$dom->encoding = "utf-8";
$dom->save('products.xml');