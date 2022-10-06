<?php

class mySQLtoXML
{
    private mysqli $mySql;
    private string $servername = "localhost";
    private string $username = "user";
    private string $password = "password";
    private string $dbname = "products";

    public function __construct() {
        $this->mySql = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
    }

    public function getProductName($product_id, $language_id): string {
        $sql = mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT name FROM product_description where product_id = '$product_id' and language_id = '$language_id'"
        ));
        return $sql['name'];
    }

    public function getProductDescription($product_id, $language_id): string {
        $sql = mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT meta_description FROM product_description where product_id = '$product_id' and language_id = '$language_id'"
        ));
        if (strlen($sql['meta_description']) > 200) {
            $x = strstr(wordwrap($sql['meta_description'], 200), "\n", true);
            return $x . "...";
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
        return date("d-m-Y", strtotime($date));
    }

    public function getSpecialPrice($product_id): string {
        $sql = mysqli_query(
            $this->mySql,
            "SELECT price, date_end FROM product_special where product_id = '$product_id'"
        );
        if ($sql->num_rows == 0) {
            return "";
        } else {
            $sql = mysqli_fetch_array(mysqli_query(
                $this->mySql,
                "SELECT price, date_end FROM product_special where product_id = '$product_id'"
            ));
            if (strtotime(date('y-m-d')) <= strtotime($sql['date_end'])) {
                return number_format($sql["price"] + ($sql["price"] / 100 * 21), 2);
            } else {
                return "";
            }
        }
    }

    public function getCategoryNumber($product_id): array {
        return mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT category_id FROM product_to_category where product_id = '$product_id'"
        ));
    }

    public function getProductCategory($category_id): string {
        $category_id = $category_id["category_id"];
        $category_name = mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT name FROM category_description where category_id = '$category_id' and language_id = 1"
        ));
        return $category_name["name"];
    }

    public function getFullProductCategory($category_id): string {
        $full_category = [$this->getProductCategory($category_id)];
        $category_id = $category_id["category_id"];

        $parent_id = mysqli_fetch_array(mysqli_query(
            $this->mySql,
            "SELECT parent_id FROM category where category_id = '$category_id'"
        ));

        while ($parent_id['parent_id'] !== "0") {
            $parent_id = $parent_id["parent_id"];
            $category = mysqli_fetch_array(mysqli_query(
                $this->mySql,
                "SELECT name FROM category_description where category_id = '$parent_id' and language_id = 1"
            ));

            $full_category[] = $category['name'];

            $parent_id = mysqli_fetch_array(mysqli_query(
                $this->mySql,
                "SELECT parent_id FROM category where category_id = '$parent_id'"
            ));
        }
        return (implode(" >> ", array_reverse($full_category)));
    }
    public function getMySql(): mysqli {
        return $this->mySql;
    }
}

$mySql = new mySQLtoXML;
$product = mysqli_query(
    $mySql->getMySql(),
    "SELECT * FROM product"
);

$dom = new DOMDocument(1.0, 'utf-8');
$dom->formatOutput = true;

try {
    $root = $dom->appendChild($dom->createElement('root'));
    while ($repository = mysqli_fetch_array($product)) {

        $item = $dom->createElement('item');
        $root->appendChild($item);

        $model = $dom->createElement('model', $repository["model"]);
        $status = $dom->createElement('status', $repository["status"]);
        $name = $dom->createElement('name');
        $description = $dom->createElement('description');

        $nameLv = $dom->createElement('lv', $mySql->getProductName($repository["product_id"], 1));
        $nameEn = $dom->createElement('en', $mySql->getProductName($repository["product_id"], 2));
        $nameRu = $dom->createElement('ru', $mySql->getProductName($repository["product_id"], 3));

        $descriptionLv = $dom->createElement('lv', $mySql->getProductDescription($repository["product_id"], 1));
        $descriptionEn = $dom->createElement('en', $mySql->getProductDescription($repository["product_id"], 2));
        $descriptionRu = $dom->createElement('ru', $mySql->getProductDescription($repository["product_id"], 3));

        $quantity = $dom->createElement('quantity', $repository["quantity"]);
        $ean = $dom->createElement('ean', $repository["ean"]);
        $image = $dom->createElement('image', $mySql->checkImageLink($repository["image"]));
        $date_added = $dom->createElement('date_added', $mySql->convertDate($repository["date_added"]));
        $price = $dom->createElement('price', number_format($repository["price"] + ($repository["price"] / 100 * 21), 2));
        $special_price = $dom->createElement('special_price', $mySql->getSpecialPrice($repository["product_id"]));
        $category = $dom->createElement('category', $mySql->getProductCategory($mySql->getCategoryNumber($repository["product_id"])));
        $full_category = $dom->createElement('full_category', htmlspecialchars_decode($mySql->getFullProductCategory($mySql->getCategoryNumber($repository["product_id"]))));

        $item->append($model, $status, $name, $description, $quantity, $ean, $image, $date_added, $price, $special_price, $category, $full_category);
        $name->append($nameLv, $nameEn, $nameRu);
        $description->append($descriptionLv, $descriptionEn, $descriptionRu);
    }
} catch (DOMException $e) {
    echo "XML write error";
}

$dom->save("products.xml");