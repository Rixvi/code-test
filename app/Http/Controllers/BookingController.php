<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $authUser = auth()->user();
        $response = [];
        if($request->get('user_id')) {

            $response = $this->repository->getUsersJobs($request->get('user_id'));

        }
        elseif($authUser->user_type === env('ADMIN_ROLE_ID') || $authUser->user_type === env('SUPER_ADMIN_ROLE_ID'))
        {
            $response = $this->repository->getAll($authUser, $request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @return mixed
     */
    public function store()
    {
        $response = $this->repository->store(auth()->user(), request()->all());

        return response($response);

    }

    /**
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $response = $this->repository->updateJob($id, array_except(request()->all(), ['_token', 'submit']), auth()->auth());

        return response($response);
    }

    /**
     * @return mixed
     */
    public function immediateJobEmail()
    {
        $response = $this->repository->storeJobEmail(request()->all());

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($userId = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($userId, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function acceptJob()
    {
        $response = $this->repository->acceptJob(request()->all(), auth()->user());

        return response($response);
    }

    public function acceptJobWithId()
    {
        $response = $this->repository->acceptJobWithId(request()->job_id, auth()->user());

        return response($response);
    }

    /**
     * @return mixed
     */
    public function cancelJob()
    {

        $response = $this->repository->cancelJobAjax(request()->all(), auth()->user());

        return response($response);
    }

    /**
     * @return mixed
     */
    public function endJob()
    {

        $response = $this->repository->endJob(request()->all());

        return response($response);

    }

    public function customerNotCall()
    {

        $response = $this->repository->customerNotCall(request()->all());

        return response($response);

    }

    /**
     * @return mixed
     */
    public function getPotentialJobs()
    {

        $response = $this->repository->getPotentialJobs(auth()->user());

        return response($response);
    }

    public function distanceFeed()
    {
        $distance = $time = $jobId = $session = $adminComment = "";

        if(!empty(request()->distance)){
            $distance = request()->distance;
        }

        if(!empty(request()->time)){
            $time = request()->time;
        }

        if(!empty(request()->job_id)){
            $jobId = request()->job_id;
        }

        if(!empty(request()->session_time)){
            $session = request()->session_time;
        }

        if(request()->flagged === 'true'){
            if(request()->admin_comment === '') return "Please, add comment";
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }

        if(!empty(request()->manually_handled)){
            $manuallyHandled = 'yes';
        }else{
            $manuallyHandled = 'no';
        }

        if(request()->by_admin === 'true'){
            $byAdmin = 'yes';
        }else{
            $byAdmin = 'no';
        }

        if(!empty(request()->admin_comment)){
            $adminComment = request()->admin_comment;
        }


        if ($time || $distance) {

            Distance::where('job_id', '=', $jobId)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($adminComment || $session || $flagged || $manuallyHandled || $byAdmin) {

            Job::where('id', '=', $jobId)->update(array('admin_comments' => $adminComment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manuallyHandled, 'by_admin' => $byAdmin));

        }

        return response('Record updated!');
    }

    public function reopen()
    {
        $response = $this->repository->reopen(request()->all());

        return response($response);
    }

    public function resendNotifications()
    {
        $job = $this->repository->find(request()->job_id);
        $this->repository->sendNotificationTranslator($this->repository->find(request()->job_id), $this->repository->jobToData($job), '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications()
    {
        $job = $this->repository->find(request()->job_id);
        $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
