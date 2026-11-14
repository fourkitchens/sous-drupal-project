<?php

use Drupal\Core\Form\FormState;
use Drupal\user\Form\UserLoginForm;
use Drupal\user\UserInterface;

$stack = \Drupal::service("request_stack");
$original = \Drupal::request();
$long = str_repeat("é", UserInterface::USERNAME_MAX_LENGTH + 5);
$html = '<svg onload="alert(1)">';
$cases = [
  "Absent name" => [[], "", "GET", FALSE],
  "Blank name" => [["name" => " \t\n "], "", "GET", FALSE],
  "Trimmed name" => [["name" => " \tsous_chef\n "], "sous_chef", "GET", FALSE],
  "Bounded Unicode" => [["name" => $long], mb_substr($long, 0, UserInterface::USERNAME_MAX_LENGTH), "GET", FALSE],
  "HTML name" => [["name" => $html], $html, "GET", FALSE],
  "Other parameters" => [["name" => "sous_chef", "pass" => "ignored", "password" => "ignored"], "sous_chef", "GET", FALSE],
  "Submitted name" => [["name" => "query-user"], "typed-user", "GET", TRUE],
  "POST name" => [["name" => "query-user"], "typed-user", "POST", FALSE],
];
// Build real GET forms; submitted cases exercise the alter without signing in.
foreach ($cases as $label => [$query, $expected, $method, $submitted]) {
  $request = $original->duplicate($query, []);
  $request->setMethod($method);
  $stack->push($request);
  try {
    $state = (new FormState())->setSubmitted($submitted);
    if ($submitted || $method !== "GET") {
      $form = UserLoginForm::create(\Drupal::getContainer())->buildForm([], $state);
      $form["name"]["#default_value"] = "typed-user";
      sous_login_prefill_form_user_login_form_alter($form, $state, "user_login_form");
    }
    else {
      $form = \Drupal::formBuilder()->getForm(UserLoginForm::class);
    }
    if (($form["name"]["#value"] ?? $form["name"]["#default_value"] ?? "") !== $expected) {
      throw new \RuntimeException("Unexpected login prefill: $label");
    }
    if (!in_array("url.query_args:name", $form["#cache"]["contexts"] ?? [], TRUE)) {
      throw new \RuntimeException("Missing login cache context: $label");
    }
    if (($form["pass"]["#value"] ?? "") !== "" || isset($form["pass"]["#default_value"])) {
      throw new \RuntimeException("Password was prefilled: $label");
    }
    if (!$submitted && $method === "GET") {
      $markup = \Drupal::service("renderer")->renderInIsolation($form["name"]);
      $document = \Drupal\Component\Utility\Html::load((string) $markup);
      $fields = new \DOMXPath($document);
      if ($fields->evaluate('string(//input[@name="name"]/@value)') !== $expected || $document->getElementsByTagName("svg")->length) {
        throw new \RuntimeException("Unexpected rendered login name: $label");
      }
    }
  }
  finally {
    $stack->pop();
  }
}
echo "Login prefill matches.\n";
