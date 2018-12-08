<?php
namespace App\Controllers;

use Framework\View;
use Framework\Controller;
use App\Helpers\AuthHelper;
use App\Helpers\SessionHelper as Session;
use App\Helpers\TraitsHelper as Traits;
use App\Models\User;
use App\Helpers\ValidationHelper as Validator;
use App\Models\Petition;
use App\Models\UserPetition;

class PetitionController extends Controller
{
	private $userAuth;

	protected function before()
    {
		if (AuthHelper::Auth())
		{
			$this->userAuth = true;
		}
		else
		{
			$this->userAuth = false;
			Traits::Redirect('/');
		}
	}
	
	public function index() {
		$title = APP_TITLE . ' :: Створення петиції';

        $isAuth = $this->userAuth;

        return View::render('createPetition', compact('title', 'isAuth'));
	}

	public function createPetition() {
		if (isset($_POST) && !empty($_POST)) {
			$user = new User();
			$user = $user->getUserByLogin($_SESSION['login']);

			if ($user)
			{
				$params = [
					'title' => Validator::clean($_POST['title']),
					'petition_text' => Validator::clean($_POST['petition_text']),
					'owner_id' => $user['id']
				];
				$pttn = new Petition();
	
				if (!$pttn->getPetitionsByTitle($params['title']))
				{
					$pttn = $pttn->createPetition($params);
					Traits::Redirect('/');
				}
				else
				{
					$errors['createPetitionError'] = 'Така петиція вже інує!';
				}
			}
			else
			{
				$errors['createPetitionError'] = 'Користувач не знайден';	
			}
		}
		else
		{
			$errors['createPetitionError'] = 'Помилка передачі данних!';
		}
		$isAuth = $this->userAuth;
		$data = [
			'title' => (isset($_POST['title'])) ? $_POST['title'] : "",
			'petition_text' => (isset($_POST['petition_text'])) ? $_POST['petition_text'] : ""
		];

		return View::render('createPetition', compact('isAuth', 'data', 'errors'));
	}

	public function subscribePetition($pttn)
	{
		$petitions = new Petition();
		$pttn = $petitions->getPetitionsById($pttn);

		if ($pttn)
		{
			$up = new UserPetition();
			if (!$up->getPetitionUserSignatures($pttn['id'], $_SESSION['user_id']))
			{
				$up->createLink([
					'user_id' => $_SESSION['user_id'],
					'petition_id' => $pttn['id']
				]);
				Traits::Redirect('/');
			}
			else
			{
				$errors['subscribe'] = urldecode('Ви вже підписали дану петицію!');
			}
		}
		else
		{
			$errors['subscribe'] = "Петиція не знайдена! Помилка підпису!";
		}
		$isAuth = $this->userAuth;
		$this->getAllPetitions($petitions);
		View::render('startPage', compact('isAuth', 'petitions', 'errors'));
	}

	private function getAllPetitions(&$petitions) {
        $petitions = new Petition();
        $petitions = $petitions->getAllPetitions();

        foreach($petitions as &$petition)
        {
            $petition['signature'] = UserPetition::getPetitionSignatures($petition['id']);
        }
    }

	 protected function after()
    {
    }
}