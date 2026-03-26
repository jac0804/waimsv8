<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // return $this->view('emails.welcome');

        // return $this->from('erick@sbc.ph')->subject($this->data['title'])->view('emails.welcome')->with('data', $this->data);
        if (isset($this->data['newformat'])) {
            if ($this->data['newformat']) return $this->from('erick@sbc.ph')->subject($this->data['subject'])->view($this->data['view'])->with('data', $this->data)->attachData($this->data['pdf'], $this->data['filename'] . '.pdf');
        }
        if (isset($this->data['pdf'])) {
            return $this->from('erick@sbc.ph')->subject($this->data['subject'])->view($this->data['view'])->with('data', $this->data)->attach($this->data['pdf'], array('as' => $this->data['filename'] . '.pdf',  'mime' => 'application/pdf'));
        } else {
            // $sendfrom = 'erick@sbc.ph';
            if (isset($this->data['excel'])) {
                return $this->from(env('MAIL_USERNAME', 'erick@sbc.ph'))->subject($this->data['subject'])->view($this->data['view'])->with('data', $this->data)->attach($this->data['excel'], ['as' => $this->data['filename'] . '.xls', 'mime' => 'application/vnd.ms-excel']);
            } else {
                $sendfrom = env('MAIL_USERNAME', 'erick@sbc.ph');
                if (isset($this->data['from'])) {
                    if ($this->data['from'] != '' && $this->data['from'] != null) {
                        $sendfrom = $this->data['from'];
                    }
                }
                if (isset($this->data['cc'])) {
                    if ($this->data['cc'] != '' && $this->data['cc'] != null) {
                        // if($this->data['hasfile']) {
                        //     return $this->from($sendfrom)->subject($this->data['subject'])->cc($this->data['cc'])->view($this->data['view'])->attachData(base64_decode($this->data['attachment']['file']), $this->data['attachment']['filename'], ['mime' => $this->data['attachment']['filetype']]);
                        // } else {
                        //     return $this->from($sendfrom)->subject($this->data['subject'])->cc($this->data['cc'])->view($this->data['view']);
                        // }
                        if (isset($this->data['hasattachment']) && $this->data['hasattachment']) {
                            $this->from($sendfrom)->subject($this->data['subject'])->cc($this->data['cc'])->view($this->data['view']);
                            foreach ($this->data['attachments'] as $a) {
                                $this->attachData($a['file'], $a['filename'], ['mime' => $a['filetype']]);
                            }
                        } else {
                            $this->from($sendfrom)->subject($this->data['subject'])->cc($this->data['cc'])->view($this->data['view']);
                        }
                        return $this;
                    }
                }
                // if ($this->data['hasfile']) {
                //     return $this->from($sendfrom)->subject($this->data['subject'])->view($this->data['view'])->attachData(base64_decode($this->data['attachment']['file']), $this->data['attachment']['filename'], ['mime' => $this->data['attachment']['filetype']]);
                // } else {
                //     return $this->from($sendfrom)->subject($this->data['subject'])->view($this->data['view']);
                // }
                if (isset($this->data['hasattachment']) && $this->data['hasattachment']) {
                    $this->from($sendfrom)->subject($this->data['subject'])->view($this->data['view']);
                    foreach ($this->data['attachments'] as $a) {
                        $this->attachData($a['file'], $a['filename'], ['mime' => $a['filetype']]);
                    }
                    return $this;
                } else {
                    return $this->from($sendfrom)->subject($this->data['subject'])->view($this->data['view']);
                }
            }
        }
        /*

        if(isset($this->data['companyid'])){
          switch ($this->data['companyid']){
              case 10:
                return $this->from('erick@sbc.ph')->subject($this->data['subject'])->view($this->data['view'])->with('data', $this->data)->attach($this->data['pdf'], array('as' => $this->data['filename'].'.pdf',  'mime' => 'application/pdf'));
                break;
               default:
                 return $this->from('erick@sbc.ph')->subject($this->data['subject'])->view($this->data['view'])->with('data', $this->data)->attach($this->data['pdf'], array('as' => $this->data['filename'].'.pdf',  'mime' => 'application/pdf'));
                break;
          }
        } else {
            return $this->from(env('MAIL_USERNAME', 'erick@sbc.ph'))->subject($this->data['name'])->view('emails.welcome')->with('data', $this->data)->attach($this->data['pdf'], array('as' => $this->data['filename'].'.pdf',  'mime' => 'application/pdf'));
        }
          */
    }
}
