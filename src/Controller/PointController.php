<?php

namespace App\Controller;

use App\Entity\Point;
use App\Repository\PointRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use FOS\RestBundle\Controller\Annotations as Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class PointController extends AbstractController
{
//    /**
//     * @Route\Post("/api/get_points", name="get_points")
//     * @param Request $request
//     * @param PointRepository $pointRepository
//     * @return JsonResponse
//     */
//    public function getPoints(Request $request, PointRepository $pointRepository): JsonResponse
//    {
//        $data = json_decode($request->getContent(), true);
//
//        $ip = isset($data['ip']) ? $data['ip'] : $_SERVER['REMOTE_ADDR'];
//        $radius = isset($data['radius']) ? $data['radius'] : 1;
//
//        $points = $pointRepository->findPoints($ip, $radius);
//
//        if (!$points) {
//            return $this->json([
//                'message' => 'Нет точек в радиусе ' . $radius,
//            ]);
//        }
//
//        return $this->json($points);
//    }

    /**
     * @Route\Post("/api/get_points_city", name="get_points_city")
     * @param Request $request
     * @param PointRepository $pointRepository
     * @return JsonResponse
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getPointsCity(Request $request, PointRepository $pointRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['city'])) {
            //        $ip = $_SERVER['REMOTE_ADDR'];
            $ip = '178.47.91.1';

            $client = HttpClient::create(['http_version' => '2.0']);

            $response = $client->request('GET', 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Token c83803c2fee23df052ad94cb6f4d37314f4ee651',
                ],
                'query' => [
                    'ip' => $ip
                ],
            ]);

            $location = $response->getContent();

            $JsonLocation = json_decode($location, true);

            $UserCity = $JsonLocation['location']['data']['city'];

        } else {
            $UserCity = $data['city'];
        }

        $offset = isset($data['offset']) ? $data['offset'] : 0;
        $limit  = isset($data['limit']) ? $data['limit'] : 50;

        $points = $pointRepository->findPointsCity($UserCity, $offset, $limit);

        if (!$points) {
            return $this->json([
                'message' => 'Нет точек в городе ' . $UserCity,
            ], Response::HTTP_OK);
        }

        return $this->json($points, Response::HTTP_OK);
    }

    /**
     * @Route\Post("/api/point", name="create_point")
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function postCreatePoint(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $entityManager = $this->getDoctrine()->getManager();

        $point = new Point();

        if (isset($data['address'])) {
            $client = HttpClient::create(['http_version' => '2s.0']);

            $address = str_replace(" ", "%20", $data['address']);

            $response = $client->request('GET', 'http://search.maps.sputnik.ru/search/addr', [
                'query' => [
                    'q' => $address,
                    'apikey' => '5032f91e8da6431d8605-f9c0c9a00357',
                ],
            ]);

            $location = $response->getContent();

            $JsonLocation = json_decode($location, true);
        } else {
            return $this->json([
                'message' => 'Не указан адрес точки!',
            ], Response::HTTP_BAD_REQUEST);
        };

        if (isset($data['name'])) {
            $point->setName($data['name']);
        } else {
            return $this->json([
                'message' => 'Не указано имя точки!',
            ], Response::HTTP_BAD_REQUEST);
        };

        if (isset($data['description'])) {
            $point->setDescription($data['description']);
        } else {
            return $this->json([
                'message' => 'Не указано описание точки!',
            ], Response::HTTP_BAD_REQUEST);
        };

        if (isset($data['type'])) {
            $point->setType($data['type']);
        } else {
            return $this->json([
                'message' => 'Не указан тип точки!',
            ], Response::HTTP_BAD_REQUEST);
        };

        $point->setCity($JsonLocation['result']['address'][0]['features'][0]['properties']['address_components'][2]['value']);

        $point->setLatitude($JsonLocation['result']['address'][0]['features'][0]['geometry']['geometries'][0]['coordinates'][0]);

        $point->setLongitude($JsonLocation['result']['address'][0]['features'][0]['geometry']['geometries'][0]['coordinates'][1]);

        $point->setCreateBy($_SERVER['REMOTE_ADDR']);


        $entityManager->persist($point);

        $entityManager->flush();

        return $this->json([
            'message' => 'Добавлена точка с ID: '. $point->getId(),
        ], Response::HTTP_CREATED);
    }

    /**
     * @Route\Patch("/api/point/{id}", name="edit_point")
     * @param $id
     * @param Request $request
     * @param PointRepository $pointRepository
     * @return JsonResponse
     */
    public function patchEditPoint($id, Request $request, PointRepository $pointRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $entityManager = $this->getDoctrine()->getManager();

        $point = $pointRepository->find($id);

        if (isset($data['name'])) {
            $point->setName($data['name']);
        };

        if (isset($data['description'])) {
            $point->setDescription($data['description']);
        };

        if (isset($data['type'])) {
            $point->setType($data['type']);
        };

        if (isset($data['city'])) {
            $point->setCity($data['city']);
        };


        $entityManager->persist($point);
        $entityManager->flush();

        return $this->json([
            'message' => 'Точка с ID: '. $point->getId().', успешно изменена!',
        ], Response::HTTP_OK);
    }

    /**
     * @Route\Delete("/api/point/{id}", name="delete_point")
     * @param $id
     * @param PointRepository $pointRepository
     * @return JsonResponse
     */
    public function deletePoint($id, PointRepository $pointRepository): JsonResponse
    {
        $point = $pointRepository->find($id);

        if (!$point) {
            return $this->json([
                'message' => 'Нет точки с ID: '. $id,
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($point);
        $entityManager->flush();

        return $this->json([
            'message' => 'Точка c ID: '. $id .', удалена!',
        ], Response::HTTP_OK);
    }
}
