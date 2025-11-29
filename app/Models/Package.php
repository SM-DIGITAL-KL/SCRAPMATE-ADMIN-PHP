<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTime; 

class Package extends Model
{
    protected $table = 'packages';

    public static function setPackage($user_id)
    {
        $package = Package::where('type', 1)->first();
        if ($package) {
            $invoice = new Invoice;
            $invoice->user_id = $user_id;
            $invoice->from_date = date('Y-m-d');
            $invoice->to_date = date('Y-m-d', strtotime('+'.$package->duration.' days'));
            $invoice->name = $package->name;
            $invoice->displayname = $package->displayname;
            $invoice->type = 'Free';
            $invoice->price = $package->price;
            $invoice->duration = $package->duration;
            $invoice->save();
            return true;
        } else {
            return true;
        }
    }

// public static function BalanceCount($user_id)
// {
//     $invoice = Invoice::where('user_id', $user_id)->latest('created_at')->first();
    
//     if (!$invoice) {
//         return 'No Subscription';
//     }
    
//     try {
//         $fromDate = new DateTime($invoice->from_date);
//         $toDate = new DateTime($invoice->to_date);
//         $currentDate = new DateTime(); // Today's date
        
//         // Check if subscription has expired (current date is after to_date)
//         if ($currentDate > $toDate) {
//             return '0 Days';
//         }
        
//         // Check if subscription hasn't started yet
//         if ($currentDate < $fromDate) {
//             $daysUntilStart = $currentDate->diff($fromDate)->days;
//             $totalDays = $fromDate->diff($toDate)->days;
//             return 'Starts in ' . $daysUntilStart . ' days (' . $totalDays . ' days total)';
//         }
        
//         // Calculate remaining days from current date to end date
//         $remainingDays = $currentDate->diff($toDate)->days;
        
//         // Add 1 to include today as a valid day
//         $remainingDays = $remainingDays + 1;
        
//         return $remainingDays . ' Days';
        
//     } catch (Exception $e) {
//         return 'Invalid Date';
//     }
// }

    public static function BalanceCount($user_id)
    {
        $today = now()->format('Y-m-d');

        // ✅ Get all active subscriptions
        $activeInvoices = Invoice::where('user_id', $user_id)
        ->where(function ($query) use ($today) {
            $query->whereDate('from_date', '<=', $today)
                ->whereDate('to_date', '>=', $today);
        })
        ->orWhere(function ($query) use ($today) {
            $query->whereDate('from_date', '>', $today); // upcoming
        })
        ->orderBy('from_date', 'asc')
        ->get();
        // return $activeInvoices;
        $totalRemainingDays = 0;
        if ($activeInvoices->isEmpty()) {
            return $totalRemainingDays . ' Days';
        }

        // ✅ If active subscription(s) found → calculate total remaining days
        
        foreach ($activeInvoices as $invoice) {
            $to_date = new DateTime($invoice->to_date);
            // if ($invoice->from_date <= $today && $to_date >= new DateTime($today)) {
            //     $totalRemainingDays += $to_date->diff(new DateTime($today))->days;
            // }
        }
        $totalRemainingDays += $to_date->diff(new DateTime($today))->days;

        return $totalRemainingDays . ' Days';
    }

    public static function checkUserPackage($user_id)
    {
        $checkInvoice = Invoice::where('user_id', $user_id)->latest('created_at')->first();
        if (!$checkInvoice) {
            $package = Package::where('type', 1)->where('status', 1)->first();
            if ($package) {
                $invoice = new Invoice;
                $invoice->user_id = $user_id;
                $invoice->from_date = date('Y-m-d');
                $invoice->to_date = date('Y-m-d', strtotime('+'.$package->duration.' days'));
                $invoice->name = $package->name;
                $invoice->displayname = $package->displayname;
                $invoice->type = 'Free';
                $invoice->price = $package->price;
                $invoice->duration = $package->duration;
                $invoice->save();
            }
        }
        return true;
    }

}


